<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTree.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerResolverInterface;
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
   */
  public function __construct(MenuTreeStorageInterface $tree_storage, MenuLinkManagerInterface $menu_link_manager, RouteProviderInterface $route_provider, MenuActiveTrailInterface $menu_active_trail, ControllerResolverInterface $controller_resolver) {
    $this->treeStorage = $tree_storage;
    $this->menuLinkManager = $menu_link_manager;
    $this->routeProvider = $route_provider;
    $this->menuActiveTrail = $menu_active_trail;
    $this->controllerResolver = $controller_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);

    $parameters = new MenuTreeParameters();
    $parameters->setActiveTrail($active_trail)
      // We want links in the active trail to be expanded.
      ->addExpandedParents($active_trail)
      // We marked the links in the active trail to be expanded, but we also
      // want their descendants that have the "expanded" flag enabled to be
      // expanded.
      ->addExpandedParents($this->treeStorage->getExpanded($menu_name, $active_trail));

    return $parameters;
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
  public function build(array $tree) {
    $tree_access_cacheability = new CacheableMetadata();
    $tree_link_cacheability = new CacheableMetadata();
    $items = $this->buildItems($tree, $tree_access_cacheability, $tree_link_cacheability);

    $build = [];

    // Apply the tree-wide gathered access cacheability metadata and link
    // cacheability metadata to the render array. This ensures that the
    // rendered menu is varied by the cache contexts that the access results
    // and (dynamic) links depended upon, and invalidated by the cache tags
    // that may change the values of the access results and links.
    $tree_cacheability = $tree_access_cacheability->merge($tree_link_cacheability);
    $tree_cacheability->applyTo($build);

    if ($items) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $item = end($items);
      $link = $item['original_link'];
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme'] = 'menu__' . strtr($menu_name, '-', '_');
      $build['#items'] = $items;
      // Set cache tag.
      $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
    }

    return $build;
  }

  /**
   * Builds the #items property for a menu tree's renderable array.
   *
   * Helper function for ::build().
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   A data structure representing the tree, as returned from
   *   MenuLinkTreeInterface::load().
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_access_cacheability
   *   Internal use only. The aggregated cacheability metadata for the access
   *   results across the entire tree. Used when rendering the root level.
   * @param \Drupal\Core\Cache\CacheableMetadata &$tree_link_cacheability
   *   Internal use only. The aggregated cacheability metadata for the menu
   *   links across the entire tree. Used when rendering the root level.
   *
   * @return array
   *   The value to use for the #items property of a renderable menu.
   *
   * @throws \DomainException
   */
  protected function buildItems(array $tree, CacheableMetadata &$tree_access_cacheability, CacheableMetadata &$tree_link_cacheability) {
    $items = array();

    foreach ($tree as $data) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if (!$link->isEnabled()) {
        continue;
      }

      if ($data->access !== NULL && !$data->access instanceof AccessResultInterface) {
        throw new \DomainException('MenuLinkTreeElement::access must be either NULL or an AccessResultInterface object.');
      }

      // Gather the access cacheability of every item in the menu link tree,
      // including inaccessible items. This allows us to render cache the menu
      // tree, yet still automatically vary the rendered menu by the same cache
      // contexts that the access results vary by.
      // However, if $data->access is not an AccessResultInterface object, this
      // will still render the menu link, because this method does not want to
      // require access checking to be able to render a menu tree.
      if ($data->access instanceof AccessResultInterface) {
        $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($data->access));
      }

      // Gather the cacheability of every item in the menu link tree. Some links
      // may be dynamic: they may have a dynamic text (e.g. a "Hi, <user>" link
      // text, which would vary by 'user' cache context), or a dynamic route
      // name or route parameters.
      $tree_link_cacheability = $tree_link_cacheability->merge(CacheableMetadata::createFromObject($data->link));

      // Only render accessible links.
      if ($data->access instanceof AccessResultInterface && !$data->access->isAllowed()) {
        continue;
      }
      $element = [];

      // Set a variable for the <li> tag. Only set 'expanded' to true if the
      // link also has visible children within the current tree.
      $element['is_expanded'] = FALSE;
      $element['is_collapsed'] = FALSE;
      if ($data->hasChildren && !empty($data->subtree)) {
        $element['is_expanded'] = TRUE;
      }
      elseif ($data->hasChildren) {
        $element['is_collapsed'] = TRUE;
      }
      // Set a helper variable to indicate whether the link is in the active
      // trail.
      $element['in_active_trail'] = FALSE;
      if ($data->inActiveTrail) {
        $element['in_active_trail'] = TRUE;
      }

      // Note: links are rendered in the menu.html.twig template; and they
      // automatically bubble their associated cacheability metadata.
      $element['attributes'] = new Attribute();
      $element['title'] = $link->getTitle();
      $element['url'] = $link->getUrlObject();
      $element['url']->setOption('set_active_class', TRUE);
      $element['below'] = $data->subtree ? $this->buildItems($data->subtree, $tree_access_cacheability, $tree_link_cacheability) : array();
      if (isset($data->options)) {
        $element['url']->setOptions(NestedArray::mergeDeep($element['url']->getOptions(), $data->options));
      }
      $element['original_link'] = $link;
      // Index using the link's unique ID.
      $items[$link->getPluginId()] = $element;
    }

    return $items;
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
