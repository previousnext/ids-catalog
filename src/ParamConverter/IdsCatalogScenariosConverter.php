<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\ids_catalog\IdsScenarios;
use Symfony\Component\Routing\Route;

/**
 * Converter for list enum shortname to enum class string.
 */
final class IdsCatalogScenariosConverter implements ParamConverterInterface {

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
    $enum = $defaults['enum'];
    if ($enum === NULL) {
      return NULL;
    }

    foreach ($this->scenarios->scenarios() as $scenario) {
      $rEnum = new \ReflectionClass(($scenario->pintoEnum ?? throw new \LogicException())::class);
      if ($enum === $rEnum->getShortName()) {
        return $rEnum->getName();
      }
    }

    return NULL;
  }

  public function applies($definition, $name, Route $route): bool {
    return \is_array($definition) && ($definition['type'] ?? '') === 'ids_catalog_enum_short_name';
  }

}
