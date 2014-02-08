<?php

/**
 * @file
 * Contains \Drupal\menu\Controller\MenuController.
 */

namespace Drupal\menu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\system\MenuInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Menu routes.
 */
class MenuController extends ControllerBase {

  /**
   * Gets all the available menus and menu items as a JavaScript array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The available menu and menu items.
   */
  public function getParentOptions(Request $request) {
    $available_menus = array();
    if ($menus = $request->request->get('menus')) {
      foreach ($menus as $menu) {
        $available_menus[$menu] = $menu;
      }
    }
    $options = _menu_get_options(menu_get_menus(), $available_menus, array('mlid' => 0));

    return new JsonResponse($options);
  }

  /**
   * Provides the menu link submission form.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link submission form.
   */
  public function addLink(MenuInterface $menu) {
    $menu_link = $this->entityManager()->getStorageController('menu_link')->create(array(
      'mlid' => 0,
      'plid' => 0,
      'menu_name' => $menu->id(),
    ));
    return $this->entityFormBuilder()->getForm($menu_link);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The menu entity.
   *
   * @return string
   *   The menu label.
   */
  public function menuTitle(MenuInterface $menu) {
    return Xss::filter($menu->label());
  }

}
