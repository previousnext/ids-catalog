<?php

declare(strict_types=1);

namespace Drupal\ids_catalog;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

final class IdsCatalogServiceProvider implements ServiceProviderInterface {

  public function register(ContainerBuilder $container): void {
    $container->addCompilerPass(new IdsCatalogCompilerPass(), priority: 100);
  }

}
