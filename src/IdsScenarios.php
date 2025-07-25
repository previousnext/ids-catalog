<?php

declare(strict_types=1);

namespace Drupal\ids_catalog;

use Pinto\PintoMapping;
use PreviousNext\IdsTools\Scenario\CompiledScenario;
use PreviousNext\IdsTools\Scenario\Scenarios;

/**
 * Memoizes scenarios once in a request so they can be looked by common object key references.
 */
final class IdsScenarios {

  /**
   * @var \SplObjectStorage<\PreviousNext\IdsTools\Scenario\CompiledScenario, object&callable>|null
   */
  private ?\SplObjectStorage $scenarios = NULL;

  public function __construct(
    private PintoMapping $pintoMapping,
  ) {
  }

  /**
   * @phpstan-return \SplObjectStorage<\PreviousNext\IdsTools\Scenario\CompiledScenario, object&callable>
   */
  public function scenarios(): \SplObjectStorage {
    return $this->scenarios ??= (function () {
      /** @var \SplObjectStorage<\PreviousNext\IdsTools\Scenario\CompiledScenario, object&callable> $scenarios */
      $scenarios = new \SplObjectStorage();
      foreach (Scenarios::findScenarios($this->pintoMapping) as $scenario => $scenarioObject) {
        $scenarios[$scenario] = $scenarioObject;
      }
      return $scenarios;
    })();
  }

  public function scenarioBefore(CompiledScenario $scenario): ?CompiledScenario {
    $scenarios = $this->scenarios();
    $previous = NULL;
    foreach ($scenarios as $s) {
      if ($s === $scenario) {
        return $previous;
      }
      $previous = $s;
    }
    return NULL;
  }

  public function scenarioAfter(CompiledScenario $scenario): ?CompiledScenario {
    $scenarios = $this->scenarios();
    $next = FALSE;
    foreach ($scenarios as $s) {
      if ($next) {
        return $s;
      }
      if ($s === $scenario) {
        $next = TRUE;
      }
    }
    return NULL;
  }

}
