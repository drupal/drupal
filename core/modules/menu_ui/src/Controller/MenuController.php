<?php

namespace Drupal\menu_ui\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Menu routes.
 */
class MenuController extends ControllerBase {

  /**
   * The menu parent form service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * Creates a new MenuController object.
   *
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_form
   *   The menu parent form service.
   */
  public function __construct(MenuParentFormSelectorInterface $menu_parent_form) {
    $this->menuParentSelector = $menu_parent_form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('menu.parent_form_selector'));
  }

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
    $available_menus = [];
    if ($menus = $request->request->get('menus')) {
      foreach ($menus as $menu) {
        $available_menus[$menu] = $menu;
      }
    }
    // @todo Update this to use the optional $cacheability parameter, so that
    //   a cacheable JSON response can be sent.
    $options = $this->menuParentSelector->getParentSelectOptions('', $available_menus);

    return new JsonResponse($options);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   The menu entity.
   *
   * @return array
   *   The menu label as a render array.
   */
  public function menuTitle(MenuInterface $menu) {
    return ['#markup' => $menu->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
