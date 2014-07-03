<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuLinkTree.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Provides loading, transforming and rendering of menu link trees.
 */
class MenuLinkTree implements MenuLinkTreeInterface {

  /**
   * The menu link tree storage.
   *
   * @var \Drupal\Core\Menu\MenuTreeStorageInterface
   */
  protected $treeStorage;

  /**
   * Service providing overrides for static links
   *
   * @var \Drupal\Core\Menu\StaticMenuLinkOverridesInterface
   */
  protected $overrides;

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
  public function getDefaultRenderedMenuTreeLinkParameters($menu_name) {
    $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);

    $parameters = new MenuTreeParameters();
    $parameters->setActiveTrail($active_trail)
      // We want links in the active trail to be expanded.
      ->addExpanded($active_trail)
      // We marked the links in the active trail to be expanded, but we also
      // want their descendants that have the "expanded" flag enabled to be
      // expanded.
      ->addExpanded($this->treeStorage->getExpanded($menu_name, $active_trail));

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
   * Helper function that recursively instantiates the plugins.
   */
  protected function createInstances($data_tree) {
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
      if (!is_callable($callable)) {
        $callable = $this->controllerResolver->getControllerFromDefinition($callable);
      }
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
    $build = array();

    foreach ($tree as $data) {
      $class = array();
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $data->link;
      // Generally we only deal with visible links, but just in case.
      if ($link->isHidden()) {
        continue;
      }
      // Set a class for the <li>-tag. Only set 'expanded' class if the link
      // also has visible children within the current tree.
      if ($data->hasChildren && !empty($data->subtree)) {
        $class[] = 'expanded';
      }
      elseif ($data->hasChildren) {
        $class[] = 'collapsed';
      }
      else {
        $class[] = 'leaf';
      }
      // Set a class if the link is in the active trail.
      if ($data->inActiveTrail) {
        $class[] = 'active-trail';
      }

      // Allow menu-specific theme overrides.
      $element['#theme'] = 'menu_link__' . strtr($link->getMenuName(), '-', '_');
      $element['#attributes']['class'] = $class;
      $element['#title'] = $link->getTitle();
      $element['#url'] = $link->getUrlObject();
      $element['#below'] = $data->subtree ? $this->build($data->subtree) : array();
      if (isset($data->options)) {
        $element['#url']->setOptions(NestedArray::mergeDeep($element['#url']->getOptions(), $data->options));
      }
      $element['#original_link'] = $link;
      // Index using the link's unique ID.
      $build[$link->getPluginId()] = $element;
    }
    if ($build) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Get the menu name from the last link.
      $menu_name = $link->getMenuName();
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme_wrappers'][] = 'menu_tree__' . strtr($menu_name, '-', '_');
      // Set cache tag.
      $build['#cache']['tags']['menu'][$menu_name] = $menu_name;
    }

    return $build;
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

}
