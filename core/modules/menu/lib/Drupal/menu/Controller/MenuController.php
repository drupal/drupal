<?php

/**
 * @file
 * Contains \Drupal\menu\Controller\MenuController.
 */

namespace Drupal\menu\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\menu_link\MenuLinkStorageControllerInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Menu routes.
 */
class MenuController implements ContainerInjectionInterface {

  /**
   * The menu link storage.
   *
   * @var \Drupal\menu_link\MenuLinkStorageControllerInterface
   */
  protected $menuLinkStorage;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a new MenuController.
   *
   * @param \Drupal\menu_link\MenuLinkStorageControllerInterface $menu_link_storage
   *   The storage controller.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(MenuLinkStorageControllerInterface $menu_link_storage, EntityManager $entity_manager) {
    $this->menuLinkStorage = $menu_link_storage;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('menu_link'),
      $container->get('entity.manager')
    );
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
    // @todo Remove this when https://drupal.org/node/1981644 is in.
    drupal_set_title(t('Add menu link'));
    $menu_link = $this->menuLinkStorage->create(array(
      'mlid' => 0,
      'plid' => 0,
      'menu_name' => $menu->id(),
    ));
    return $this->entityManager->getForm($menu_link);
  }

}
