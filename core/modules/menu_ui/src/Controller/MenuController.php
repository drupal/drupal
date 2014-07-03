<?php

/**
 * @file
 * Contains \Drupal\menu_ui\Controller\MenuController.
 */

namespace Drupal\menu_ui\Controller;

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
    /** @var \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector */
    $menu_parent_selector = \Drupal::service('menu.parent_form_selector');
    $options = $menu_parent_selector->getParentSelectOptions('', $available_menus);

    return new JsonResponse($options);
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
