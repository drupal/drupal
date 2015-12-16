<?php

/**
 * @file
 * Contains \Drupal\toolbar\Menu\ToolbarMenuLinkTree.
 */

namespace Drupal\toolbar\Menu;

use Drupal\Core\Menu\MenuLinkTree;

/**
 * Extends MenuLinkTree to add specific theme suggestions for the toolbar.
 */
class ToolbarMenuLinkTree extends MenuLinkTree {

  /**
   * {@inheritdoc}
   */
  public function build(array $tree, $level = 0) {
    if ($level == 0) {
      if (!$tree) {
        return array();
      }
      $build = parent::build($tree, $level);

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $first_link = reset($tree)->link;
      // Get the menu name of the first link.
      $menu_name = $first_link->getMenuName();
      // Add a more specific theme suggestion to differentiate this rendered
      // menu from others.
      $build['#menu_name'] = $menu_name;
      $build['#theme'] = 'menu__toolbar__' . strtr($menu_name, '-', '_');
      return $build;
    }
    else {
      return parent::build($tree, $level);
    }
  }

}
