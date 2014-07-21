<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Controller\MenuController.
 */

namespace Drupal\menu_link_content\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\MenuInterface;

/**
 * Defines a route controller for a form for menu link content entity creation.
 */
class MenuController extends ControllerBase {

  /**
   * Provides the menu link creation form.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addLink(MenuInterface $menu) {
    $menu_link = $this->entityManager()->getStorage('menu_link_content')->create(array(
      'id' => '',
      'parent' => '',
      'menu_name' => $menu->id(),
      'bundle' => 'menu_link_content',
    ));
    return $this->entityFormBuilder()->getForm($menu_link);
  }

}
