<?php

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
    $menu_link = $this->entityTypeManager()
      ->getStorage('menu_link_content')
      ->create([
        'menu_name' => $menu->id(),
      ]);
    return $this->entityFormBuilder()->getForm($menu_link);
  }

}
