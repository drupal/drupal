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
    $route = $route_match->getRouteObject();
    // Load links in the 'admin' menu matching this route. First, try to find
    // the menu link using all specified parameters.
    $links = $this->menuLinkManager->loadLinksByRoute($route_match->getRouteName(), $parameters, 'admin');
    // If the menu link was not found, try finding it without the parameters
    // that match the route defaults. Depending on whether the parameter is
    // specified in the menu item with a value matching the default, or not
    // specified at all, will change how it is stored in the menu_tree table. In
    // both cases the route match parameters will always include the default
    // parameters. This fallback method of finding the menu item is needed so
    // that menu items will work in either case.
    // @todo Remove this fallback in https://drupal.org/i/3359511.
    if (empty($links)) {

      $parameters_without_defaults = array_filter($parameters, fn ($key) => !$route->hasDefault($key) || $route->getDefault($key) !== $parameters[$key], ARRAY_FILTER_USE_KEY);
      $links = $this->menuLinkManager->loadLinksByRoute($route_match->getRouteName(), $parameters_without_defaults, 'admin');
    }
    if (empty($links)) {
      // If we did not find a link then we have no opinion on access.
      return AccessResult::neutral();
    }
    return $this->hasAccessToChildMenuItems(reset($links), $account)->cachePerPermissions();
  }

  /**
   * Check that the given route has access to child routes.
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

    $route = $this->router->getRouteCollection()->get($link->getRouteName());
    if ($route && empty($route->getRequirement('_access_admin_menu_block_page')) && empty($route->getRequirement('_access_admin_overview_page'))) {
      return AccessResult::allowed();
    }

    foreach ($this->menuLinkTree->load(NULL, $parameters) as $element) {
      if (!$this->accessManager->checkNamedRoute($element->link->getRouteName(), $element->link->getRouteParameters(), $account)) {
        continue;
      }

      // If access is allowed to this element in the tree, check for access to
      // any of its own children.
      if ($this->hasAccessToChildMenuItems($element->link, $account)->isAllowed()) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::neutral();
  }

}
