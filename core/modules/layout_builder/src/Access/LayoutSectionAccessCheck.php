<?php

namespace Drupal\layout_builder\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides an access check for the Layout Builder UI.
 *
 * @internal
 */
class LayoutSectionAccessCheck implements AccessInterface {

  /**
   * Checks routing access to the layout.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $section_storage = $route_match->getParameter('section_storage');

    if (empty($section_storage)) {
      return AccessResult::forbidden()->addCacheContexts(['route']);
    }

    if (!$section_storage instanceof SectionStorageInterface) {
      $access = AccessResult::forbidden();
    }
    else {
      $access = AccessResult::allowedIfHasPermission($account, 'configure any layout');
    }

    return $access->addCacheableDependency($section_storage);
  }

}
