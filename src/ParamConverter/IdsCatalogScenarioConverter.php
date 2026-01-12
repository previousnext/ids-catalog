<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\ids_catalog\IdsScenarios;
use Pinto\List\ObjectListInterface;
use Symfony\Component\Routing\Route;

/**
 * Multipurpose converter for three parameters comprising a scenario.
 */
final class IdsCatalogScenarioConverter implements ParamConverterInterface {

  public function __construct(
    private IdsScenarios $scenarios,
  ) {
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $defaults
   * @phpstan-ignore-next-line
   */
  public function convert($value, $definition, $name, array $defaults) {
    /** @var \Symfony\Component\HttpFoundation\InputBag<string>|null $rawVariables */
    $rawVariables = $defaults['_raw_variables'] ?? NULL;

    $enum = $rawVariables?->get('enum') ?? $defaults['enum'];
    $case = $rawVariables?->get('case') ?? $defaults['case'];
    $scenarioId = $rawVariables?->get('scenario') ?? $defaults['scenario'];
    if ($enum === NULL || $case === NULL || $scenarioId === NULL) {
      return NULL;
    }

    // If $case was converted by a previous convert() iteration, downcast it for
    // comparison purposes in the loop below.
    if ($case instanceof ObjectListInterface) {
      $case = $case->name;
    }

    foreach ($this->scenarios->scenarios() as $scenario) {
      $rEnum = new \ReflectionClass(($scenario->pintoEnum ?? throw new \LogicException())::class);
      $enumScenario = $rEnum->getShortName();
      $caseScenario = $scenario->pintoEnum->name;
      $scenarioIdScenario = $scenario->id;
      if ($enum === $enumScenario && $case === $caseScenario && $scenarioId === $scenarioIdScenario) {
        return match ($name) {
          'enum' => $enumScenario,
          'case' => $scenario->pintoEnum,
          'scenario' => $scenario,
          default => throw new \LogicException('Impossible'),
        };
      }
    }

    return NULL;
  }

  public function applies($definition, $name, Route $route): bool {
    /** @var null|array{type?: string} $definition */
    return \is_array($definition) && \str_starts_with($definition['type'] ?? '', 'ids_catalog_scenario:');
  }

}
