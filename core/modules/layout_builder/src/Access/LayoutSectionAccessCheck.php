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
 *
 * @todo Deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use
 *   \Drupal\layout_builder\Access\LayoutBuilderAccessCheck instead. See
 *   https://www.drupal.org/node/3039551.
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
    @trigger_error(__NAMESPACE__ . '\LayoutSectionAccessCheck is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\layout_builder\Access\LayoutBuilderAccessCheck instead. See https://www.drupal.org/node/3039551.', E_USER_DEPRECATED);
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
