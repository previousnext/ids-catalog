<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\Controller;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ids_catalog\IdsScenarios;

final class Scenarios extends ControllerBase {

  public function __construct(
    private IdsScenarios $scenarios,
  ) {
  }

  /**
   * @phpstan-param class-string<\Pinto\List\ObjectListInterface>|null $enum
   * @phpstan-return array<string, mixed>
   */
  public function __invoke(?string $enum = NULL): array {
    // Sanity check:
    if ($enum !== NULL && FALSE === \class_exists($enum)) {
      throw new \LogicException('Impossible.');
    }

    $build = [];
    $build['#title'] = $enum === NULL
      ? $this->t('All scenarios in all enums')
      : $this->t('All scenarios in <code>@enum</code>', ['@enum' => $enum]);

    (new CacheableMetadata())
      ->setCacheMaxAge(0)
      ->applyTo($build);

    $scenarios = $this->scenarios->scenarios();
    foreach ($scenarios as $scenario) {
      // Optionally filter by enum.
      if ($enum !== NULL && $scenario->pintoEnum::class !== $enum) {
        continue;
      }

      $scenarioSubject = $scenarios[$scenario];
      $build[(string) $scenario]['title'] = ['#markup' => '<strong>' . (string) $scenario . '</strong>'];
      $build[(string) $scenario]['object']['#prefix'] = '<div>';
      $build[(string) $scenario]['object']['object'] = ($scenarioSubject->obj)();
      $build[(string) $scenario]['object']['#suffix'] = '</div>';
      $build[(string) $scenario][] = ['#markup' => '<hr />'];
    }

    return $build;
  }

}
