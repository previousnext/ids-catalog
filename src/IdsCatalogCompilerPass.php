<?php

declare(strict_types=1);

namespace Drupal\ids_catalog;

use PreviousNext\IdsTools\Pinto\IdsToolsList;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class IdsCatalogCompilerPass implements CompilerPassInterface {

  public function process(ContainerBuilder $container): void {
    /** @var array<class-string<\Pinto\List\ObjectListInterface>> $pintoLists */
    $pintoLists = $container->getParameter('pinto.lists');
    \array_push($pintoLists, ...[IdsToolsList::class]);
    $container->setParameter('pinto.lists', $pintoLists);
  }

}
