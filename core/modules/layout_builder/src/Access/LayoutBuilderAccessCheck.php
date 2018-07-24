<?php

namespace Drupal\layout_builder\Access;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access check for the Layout Builder defaults.
 *
 * @internal
 */
class LayoutBuilderAccessCheck implements AccessInterface {

  /**
   * Checks routing access to the layout.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(SectionStorageInterface $section_storage, AccountInterface $account, Route $route) {
    $operation = $route->getRequirement('_layout_builder_access');
    $access = $section_storage->access($operation, $account, TRUE);
    if ($access instanceof RefinableCacheableDependencyInterface) {
      $access->addCacheableDependency($section_storage);
    }
    return $access;
  }

}
