<?php

declare(strict_types=1);

namespace Drupal\navigation\Menu;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTree;
use Drupal\Core\Menu\MenuTreeStorageInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Utility\CallableResolver;

/**
 * Extends MenuLinkTree to add specific theme suggestions for the navigation.
 *
 * @internal
 */
final class NavigationMenuLinkTree extends MenuLinkTree {

  /**
   * Constructs a \Drupal\navigation\Menu\NavigationMenuLinkTree object.
   *
   * @param \Drupal\Core\Menu\MenuTreeStorageInterface $treeStorage
   *   The menu link tree storage.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menuActiveTrail
   *   The active menu trail service.
   * @param \Drupal\Core\Utility\CallableResolver $callableResolver
   *   The callable resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(
    MenuTreeStorageInterface $treeStorage,
    MenuLinkManagerInterface $menuLinkManager,
    RouteProviderInterface $routeProvider,
    MenuActiveTrailInterface $menuActiveTrail,
    CallableResolver $callableResolver,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($treeStorage, $menuLinkManager, $routeProvider, $menuActiveTrail, $callableResolver);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $tree): array {
    if (!$tree) {
      return [];
    }
    $build = parent::build($tree);

    if (empty($build['#items'])) {
      return [];
    }

    /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
    $first_link = reset($tree)->link;
    // Get the menu name of the first link.
    $menu_name = $first_link->getMenuName();
    // Add a more specific theme suggestion to differentiate this rendered
    // menu from others.
    $build['#menu_name'] = $menu_name;
    $build['#theme'] = 'navigation_menu__' . strtr($menu_name, '-', '_');

    // Loop through menu items and add the plugin id as a class.
    foreach ($tree as $item) {
      if ($item->access->isAllowed()) {
        $plugin_id = $item->link->getPluginId();
        $plugin_class = Html::getClass(str_replace('.', '_', $plugin_id));
        $build['#items'][$plugin_id]['class'] = $plugin_class;
        $url = $build['#items'][$plugin_id]['url'];
        $icon_defaults = [
          'pack_id' => 'navigation',
          'icon_id' => $plugin_class,
          'settings' => [
            'class' => 'toolbar-button__icon',
            'size' => 20,
          ],
        ];
        $build['#items'][$plugin_id]['icon'] = NestedArray::mergeDeep($icon_defaults, $url->getOption('icon') ?? []);
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(array $tree, array $manipulators) {
    $tree = parent::transform($tree, $manipulators);
    $this->moduleHandler->alter('navigation_menu_link_tree', $tree);
    return $tree;
  }

}
