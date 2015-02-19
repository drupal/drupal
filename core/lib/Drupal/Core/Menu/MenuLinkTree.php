<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTree.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Template\Attribute;

/**
 * Implements the loading, transforming and rendering of menu link trees.
 */
class MenuLinkTree implements MenuLinkTreeInterface {

  /**
   * The menu link tree storage.
   *
   * @var \Drupal\Core\Menu\MenuTreeStorageInterface
   */
  protected $treeStorage;

  /**
   * The route provider to load routes by name.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Stores the cached current route parameters by menu and current route match.
   *
   * @todo Remove this non-static caching in
   *   https://www.drupal.org/node/1805054.
   *
   * @var \Drupal\Core\Menu\MenuTreeParameters[]
   */
  protected $cachedCurrentRouteParameters;

  /**
   * Constructs a \Drupal\Core\Menu\MenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $tree_storage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   * @param \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver
   *   The controller resolver.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, MenuLinkManagerInterface $menu_link_manager, RouteProviderInterface $route_provider, MenuActiveTrailInterface $menu_active_trail, ControllerResolverInterface $controller_resolver, CacheBackendInterface $cache, RouteMatchInterface $route_match) {
    $this->treeStorage = $tree_storage;
    $this->menuLinkManager = $menu_link_manager;
    $this->routeProvider = $route_provider;
    $this->menuActiveTrail = $menu_active_trail;
    $this->controllerResolver = $controller_resolver;
    // @todo Remove these two in https://www.drupal.org/node/1805054.
    $this->cache = $cache;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    $route_parameters = $this->routeMatch->getRawParameters()->all();
    ksort($route_parameters);
    $cid = 'current-route-parameters:' . $menu_name . ':route:' . $this->routeMatch->getRouteName() . ':route_parameters:' . serialize($route_parameters);

    if (!isset($this->cachedCurrentRouteParameters[$cid])) {
      $cache = $this->cache->get($cid);
      if ($cache && $cache->data) {
        $parameters = $cache->data;
      }
      else {
        $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);

        $parameters = new MenuTreeParameters();
        $parameters->setActiveTrail($active_trail)
          // We want links in the active trail to be expanded.
          ->addExpandedParents($active_trail)
          // We marked the links in the active trail to be expanded, but we also
          // want their descendants that have the "expanded" flag enabled to be
          // expanded.
          ->addExpandedParents($this->treeStorage->getExpanded($menu_name, $active_trail));

        $this->cache->set($cid, $parameters, CacheBackendInterface::CACHE_PERMANENT, array('config:system.menu.' . $menu_name));
      }
      $this->cachedCurrentRouteParameters[$menu_name] = $parameters;
    }

    return $this->cachedCurrentRouteParameters[$menu_name];
  }

  /**
   * {@inheritdoc}
   */
  public function load($menu_name, MenuTreeParameters $parameters) {
    $data = $this->treeStorage->loadTreeData($menu_name, $parameters);
    // Pre-load all the route objects in the tree for access checks.
    if ($data['route_names']) {
      $this->routeProvider->getRoutesByNames($data['route_names']);
    }
    return $this->createInstances($data['tree']);
  }

  /**
   * Returns a tree containing of MenuLinkTreeElement based upon tree data.
   *
   * This method converts the tree representation as array coming from the tree
   * storage to a tree containing a list of MenuLinkTreeElement[].
   *
   * @param array $data_tree
   *   The tree data coming from the menu tree storage.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   An array containing the elements of a menu tree.
   */
  protected function createInstances(array $data_tree) {
    $tree = array();
    foreach ($data_tree as $key => $element) {
      $subtree = $this->createInstances($element['subtree']);
      // Build a MenuLinkTreeElement out of the menu tree link definition:
      // transform the tree link definition into a link definition and store
      // tree metadata.
      $tree[$key] = new MenuLinkTreeElement(
        $this->menuLinkManager->createInstance($element['definition']['id']),
        (bool) $element['has_children'],
        (int) $element['depth'],
        (bool) $element['in_active_trail'],
        $subtree
      );
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(array $tree, array $manipulators) {
    foreach ($manipulators as $manipulator) {
      $callable = $manipulator['callable'];
      $callable = $this->controllerResolver->getControllerFromDefinition($callable);
      // Prepare the arguments for the menu tree manipulator callable; the first
      // argument is always the menu link tree.
      if (isset($manipulator['args'])) {
        array_unshift($manipulator['args'], $tree);
        $tree = call_user_func_array($callable, $manipulator['args']);
      }
      else {
        $tree = call_user_func($callable, $tree);
      }
    }
    return $tree;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $tree, $level = 0) {
    $items = array();

    foreach ($tree as $data) {
      $class = ['menu-item'];
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }
      // Set a class for the <li>-tag. Only set 'expanded' class if the link
      // also has visible children within the current tree.
      if ($data->hasChildren && !empty($data->subtree)) {
        $class[] = 'menu-item--expanded';
      }
      elseif ($data->hasChildren) {
        $class[] = 'menu-item--collapsed';
      }
      // Set a class if the link is in the active trail.
      if ($data->inActiveTrail) {
        $class[] = 'menu-item--active-trail';
      }

      // Allow menu-specific theme overrides.
      $element = array();
      $element['attributes'] = new Attribute();
      $element['attributes']['class'] = $class;
      $element['title'] = $link->getTitle();
      $element['url'] = $link->getUrlObject();
      $element['url']->setOption('set_active_class', TRUE);
      $element['below'] = $data->subtree ? $this->build($data->subtree, $level + 1) : array();
      if (isset($data->options)) {
        $element['url']->setOptions(NestedArray::mergeDeep($element['url']->getOptions(), $data->options));
      }
      $element['original_link'] = $link;
      // Index using the link's unique ID.
      $items[$link->getPluginId()] = $element;
    }

    if (!$items) {
      return array();
    }
    elseif ($level == 0) {
      $build = array();
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'menu__' . strtr($menu_name, '-', '_');
      $build['#items'] = $items;
      // Set cache tag.
      $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
      return $build;
    }
    else {
      return $items;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function maxDepth() {
    return $this->treeStorage->maxDepth();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtreeHeight($id) {
    return $this->treeStorage->getSubtreeHeight($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getExpanded($menu_name, array $parents) {
    return $this->treeStorage->getExpanded($menu_name, $parents);
  }

}
