<?php

namespace Drupal\system\Access;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\AccessAwareRouter;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for routes implementing _access_admin_menu_block_page.
 *
 * @see \Drupal\system\EventSubscriber\AccessRouteAlterSubscriber
 * @see \Drupal\system\Controller\SystemController::systemAdminMenuBlockPage()
 */
class SystemAdminMenuBlockAccessCheck implements AccessInterface {

  /**
   * Constructs a new SystemAdminMenuBlockAccessCheck.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree
   *   The menu link tree service.
   * @param \Drupal\Core\Routing\AccessAwareRouter $router
   *   The router service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The menu link manager service.
   */
  public function __construct(
    private readonly AccessManagerInterface $accessManager,
    private readonly MenuLinkTreeInterface $menuLinkTree,
    private readonly AccessAwareRouter $router,
    private readonly MenuLinkManagerInterface $menuLinkManager,
  ) {
  }

  /**
   * Checks access.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The cron key.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $parameters = $route_match->getParameters()->all();
    // Load links in the 'admin' menu matching this route.
    $links = $this->menuLinkManager->loadLinksByRoute($route_match->getRouteName(), $parameters, 'admin');
    if (empty($links)) {
      // If we did not find a link then we have no opinion on access.
      return AccessResult::neutral();
    }
    return $this->hasAccessToChildMenuItems(reset($links), $account)->cachePerPermissions();
  }

  /**
   * Check that the given route has access to one of it's child routes.
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $link
   *   The menu link.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasAccessToChildMenuItems(MenuLinkInterface $link, AccountInterface $account): AccessResultInterface {
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($link->getPluginId())
      ->excludeRoot()
      ->setTopLevelOnly()
      ->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load(NULL, $parameters);

    if (empty($tree)) {
      $route = $this->router->getRouteCollection()->get($link->getRouteName());
      if ($route) {
        return AccessResult::allowedIf(empty($route->getRequirement('_access_admin_menu_block_page')));
      }
      return AccessResult::neutral();
    }

    foreach ($tree as $element) {
      if (!$this->accessManager->checkNamedRoute($element->link->getRouteName(), $element->link->getRouteParameters(), $account)) {
        continue;
      }

      // If access is allowed to this element in the tree check for access to
      // its own children.
      return AccessResult::allowedIf($this->hasAccessToChildMenuItems($element->link, $account)->isAllowed());
    }
    return AccessResult::neutral();
  }

}
