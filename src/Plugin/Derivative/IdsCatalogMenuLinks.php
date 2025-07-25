<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ids_catalog\IdsScenarios;
use Drupal\ids_catalog\IdsScenarioUrl;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class IdsCatalogMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  public function __construct(
    private IdsScenarios $scenarios,
    TranslationInterface $translation,
  ) {
    $this->setStringTranslation($translation);
  }

  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get(IdsScenarios::class),
      $container->get(TranslationInterface::class),
    );
  }

  /**
   * @phpstan-param array<string, mixed>&array{id: string} $base_plugin_definition
   * @phpstan-return array<string, mixed>
   * @phpstan-ignore-next-line
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $links = [];

    $weight = 0;
    $enumShorts = [];
    $scenarios = $this->scenarios->scenarios();
    foreach ($scenarios as $scenario) {
      $scenarioObject = $scenarios[$scenario];

      $weight++;
      $url = IdsScenarioUrl::fromScenario($scenario);

      $rEnum = new \ReflectionClass(($scenario->pintoEnum ?? throw new \LogicException())::class);
      $enumShort = $rEnum->getShortName();
      $enumShorts[$enumShort] = $rEnum;
      $id = \sprintf('%s|%s|%s', $enumShort, $scenario->pintoEnum->name, $scenario->id);
      $links[$id] = [
        'title' => $this->t('@enum::@case', [
          '@enum' => $enumShort,
          '@case' => $scenario->pintoEnum->name,
        ]),
        'description' => $this->t('Class: <code>@class</code><br />Scenario: <code>@scenarioDescription</code>', [
          '@class' => $scenarioObject::class,
          '@scenarioDescription' => (string) $scenario,
        ]),
        'route_name' => $url->getRouteName(),
        'route_parameters' => $url->getRouteParameters(),
        'parent' => \sprintf('%s:enum.%s.list', $base_plugin_definition['id'], $enumShort),
        'weight' => $weight,
      ] + $base_plugin_definition;
    }

    foreach ($enumShorts as $enumShort => $rEnum) {
      $id = 'enum.' . $enumShort;
      $links[$id] = [
        'title' => $this->t('@enum', [
          '@enum' => $enumShort,
        ]),
        'description' => $this->t('Class: <code>@class</code>', [
          '@class' => $rEnum->getName(),
        ]),
        'route_name' => 'ids.report.enum',
        'route_parameters' => ['enum' => $enumShort],
        'parent' => 'ids.report.root',
        'weight' => $weight,
      ] + $base_plugin_definition;

      $id = 'enum.' . $enumShort . '.scenarios';
      $links[$id] = [
        'title' => $this->t('All scenarios'),
        'description' => $this->t('Single page of all scenarios in @enum.', [
          '@enum' => $enumShort,
        ]),
        'route_name' => 'ids.catalog.enum',
        'route_parameters' => ['enum' => $enumShort],
        'parent' => $base_plugin_definition['id'] . ':enum.' . $enumShort,
        'weight' => 1,
      ] + $base_plugin_definition;

      $id = 'enum.' . $enumShort . '.list';
      $links[$id] = [
        'title' => $this->t('List of scenarios', [
          '@enum' => $enumShort,
        ]),
        'description' => $this->t('Render each scenario individually.', [
          '@enum' => $enumShort,
        ]),
        'route_name' => 'ids.report.enum.scenarios',
        'route_parameters' => ['enum' => $enumShort],
        'parent' => $base_plugin_definition['id'] . ':enum.' . $enumShort,
        'weight' => 2,
      ] + $base_plugin_definition;
    }

    return $links;
  }

}
