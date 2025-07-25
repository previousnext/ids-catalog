<?php

declare(strict_types=1);

namespace Drupal\ids_catalog\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;
use PreviousNext\Ds\Common\Utility;
use PreviousNext\IdsTools\Pinto\IdsToolsList;
use PreviousNext\IdsTools\Pinto\Utility\Twig;

final class IdsCatalogHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * @phpstan-param array<string, mixed> $info
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if ('ids_catalog' === $file->getName()) {
      $r = new \ReflectionClass(IdsToolsList::class);
      $fileName = $r->getFileName();
      if ($fileName === FALSE) {
        throw new \LogicException('Impossible.');
      }

      // In components/ComponentsRegistry, ltrim disallows absolute dirs, so we
      // must recompute where vendor is in relation to the DrupalRoot, even if
      // it means navigating below Drupal.
      // https://www.drupal.org/project/components/issues/3210853
      // '/' indicates relative to DRUPAL_ROOT, not disk-root.
      $packageRoot = \Safe\realpath(\dirname($fileName) . '/..');
      // @phpstan-ignore-next-line
      $info['components']['namespaces'][Twig::NAMESPACE] = '/' . Utility\Twig::computePathFromDrupalRootTo($packageRoot);
    }
  }

}
