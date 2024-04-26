<?php

declare(strict_types=1);

namespace Drupal\navigation\Menu;

use Drupal\Core\Menu\MenuLinkTree;

/**
 * Extends MenuLinkTree to add specific theme suggestions for the navigation.
 *
 * @internal
 */
final class NavigationMenuLinkTree extends MenuLinkTree {

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
        $plugin_class = str_replace('.', '_', $plugin_id);
        $build['#items'][$plugin_id]['class'] = $plugin_class;
      }
    }

    return $build;
  }

}
