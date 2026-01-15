<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\Routing;

use Drupal\Core\Routing\EnhancerInterface;
use Drupal\ids_catalog\IdsScenarios;
use Symfony\Component\HttpFoundation\Request;

class IdsCatalogRouteEnhancer implements EnhancerInterface {

  public function __construct(
    private IdsScenarios $scenarios,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * @template T of array
   * @phpstan-param T $defaults
   * @phpstan-return T
   */
  public function enhance(array $defaults, Request $request): array {
    if ($defaults['_route'] === 'ids.catalog.scenario') {
      /** @var \PreviousNext\IdsTools\Scenario\CompiledScenario $requestScenario */
      $requestScenario = $defaults['scenario'] ?? throw new \LogicException();

      $defaults['scenarioSubject'] = $this->scenarios->scenarios()[$requestScenario] ?? throw new \LogicException('Impossible');
    }

    /** @var T */
    return $defaults;
  }

}
