<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuActiveTrail.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default implementation of the active menu trail service.
 */
class MenuActiveTrail implements MenuActiveTrailInterface {

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a \Drupal\Core\Menu\MenuActiveTrail object.
   *
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request object for the controller resolver and finding the active link.
   */
  public function __construct(MenuLinkManagerInterface $menu_link_manager, RequestStack $request_stack) {
    $this->menuLinkManager = $menu_link_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTrailIds($menu_name) {
    // Parent ids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with parent == ''.
    $active_trail = array('' => '');

    // If a link in the given menu indeed matches the route, then use it to
    // complete the active trail.
    if ($active_link = $this->getActiveLink($menu_name)) {
      $active_trail = $this->menuLinkManager->getParentIds($active_link->getPluginId()) + $active_trail;
    }

    return $active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTrailCacheKey($menu_name) {
    return 'menu_trail.' . implode('|', $this->getActiveTrailIds($menu_name));
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    $request = $this->requestStack->getCurrentRequest();

    // If the current request is inaccessible, then we cannot find the active
    // link, because if it existed, the current user wouldn't be able to see it.
    if ($request->attributes->get('_exception_statuscode') == 403) {
      return NULL;
    }

    if ($route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME)) {
      $route_parameters = $request->attributes->get('_raw_variables')->all();

      // Load links matching this route.
      $links = $this->menuLinkManager->loadLinksByRoute($route_name, $route_parameters, $menu_name);
      if (empty($links)) {
        return NULL;
      }

      // Select one of the matching links: pick the first, after sorting by key.
      // @todo Improve this; add MenuTreeStorage::loadByRoute(), which should allow for more control over which link is selected when there are multiple matches.
      ksort($links);
      $active_link = reset($links);

      return $active_link;
    }

    return NULL;
  }

}
