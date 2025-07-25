<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\Controller;

use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\ids_catalog\IdsScenarios;
use Drupal\ids_catalog\IdsScenarioUrl;
use PreviousNext\IdsTools\Pinto\VisualRegressionContainer\VisualRegressionContainer;
use PreviousNext\IdsTools\Scenario\CompiledScenario;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Scenario extends ControllerBase {

  public function __construct(
    private AssetResolverInterface $assetResolver,
    private RendererInterface $renderer,
    private IdsScenarios $scenarios,
    #[Autowire('asset.css.collection_renderer')]
    private AssetCollectionRendererInterface $cssCollectionRenderer,
    #[Autowire('asset.js.collection_renderer')]
    private AssetCollectionRendererInterface $jsCollectionRenderer,
    LanguageManagerInterface $languageManager,
  ) {
    $this->languageManager = $languageManager;
  }

  public function __invoke(CompiledScenario $scenario, object $scenarioObject): HtmlResponse {
    \assert(\is_callable($scenarioObject));

    // @todo change this entire page to just render in the frontend theme (?!?)
    // or make configurable, via a service parameter, that can be toggled in something like settings.local.yml, which
    // then affects this controller and the `Scenarios` controller.
    $before = $this->scenarios->scenarioBefore($scenario);
    $after = $this->scenarios->scenarioAfter($scenario);

    $build = $scenarioObject();

    $render_context = new RenderContext();
    $inner = NULL;
    $this->renderer->executeInRenderContext($render_context, function () use (&$build, &$inner): void {
      $inner = $this->renderer->render($build);
    });

    $build = [];
    // Bubble up attachments from render context.
    while ($render_context->count() > 0 && ($context = $render_context->pop()) instanceof BubbleableMetadata) {
      $context->applyTo($build);
    }

    // See also HtmlResponseAttachmentsProcessor.
    $assets = AttachedAssets::createFromRenderArray($build);
    // @todo use header?
    [$js_assets_header, $js_assets_footer] = $this->assetResolver->getJsAssets($assets, optimize:  FALSE, language: $this->languageManager->getCurrentLanguage());

    $outer = VisualRegressionContainer::create($inner);
    $outer->enum = \sprintf('%s::%s', $scenario->pintoEnum::class, $scenario->pintoEnum->name);
    $outer->objectClass = $scenarioObject::class;
    $outer->scenario = (string) $scenario;
    $outer->previousHref = $before !== NULL ? IdsScenarioUrl::fromScenario($before)->toString() : NULL;
    $outer->nextHref = $after !== NULL ? IdsScenarioUrl::fromScenario($after)->toString() : NULL;

    foreach ($this->jsCollectionRenderer->render($js_assets_footer) as $j) {
      $src = $j['#attributes']['src'];
      unset($j['#attributes']['src']);
      $definition = [];
      $definition['attributes'] = new Attribute($j['#attributes']);
      $outer->js[] = ['src' => $src] + $definition;
    }

    foreach ($this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, optimize: FALSE, language: $this->languageManager->getCurrentLanguage())) as $c) {
      $href = $c['#attributes']['href'];
      unset($c['#attributes']['href']);
      $definition = [];
      $definition['attributes'] = new Attribute($c['#attributes']);
      $outer->css[] = ['href' => $href] + $definition;
    }

    $outerRendered = $outer();

    (new CacheableMetadata())
      ->setCacheMaxAge(0)
      ->applyTo($outerRendered);

    $context = new RenderContext();
    $a = NULL;
    $this->renderer->executeInRenderContext($context, function () use (&$outerRendered, &$a): void {
      $a = $this->renderer->render($outerRendered);
    });

    $response = new HtmlResponse();
    $response->setContent($a);

    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      \assert($bubbleable_metadata instanceof BubbleableMetadata);
      $response->addCacheableDependency($bubbleable_metadata);
      $response->addAttachments($bubbleable_metadata->getAttachments());
    }

    return $response;
  }

}
