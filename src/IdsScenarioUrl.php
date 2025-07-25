<?php

declare(strict_types=1);

namespace Drupal\ids_catalog;

use Drupal\Core\Url;
use PreviousNext\IdsTools\Scenario\CompiledScenario;

final class IdsScenarioUrl {

  public static function fromScenario(CompiledScenario $scenario): Url {
    $rEnum = new \ReflectionClass(($scenario->pintoEnum ?? throw new \LogicException())::class);
    return Url::fromRoute('ids.catalog.scenario', [
      'enum' => $rEnum->getShortName(),
      'case' => $scenario->pintoEnum->name,
      'scenario' => $scenario->id,
    ]);
  }

}
