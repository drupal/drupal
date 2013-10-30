<?php

/**
 * @file
 * Contains \Drupal\book\BookBreadcrumbBuilder.
 */

namespace Drupal\book;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a breadcrumb builder for nodes in a book.
 */
class BookBreadcrumbBuilder extends BreadcrumbBuilderBase {

  /**
   * The menu link storage controller.
   *
   * @var \Drupal\menu_link\MenuLinkStorageControllerInterface
   */
  protected $menuLinkStorage;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs the BookBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Access\AccessManager $access_manager
   *   The access manager.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccessManager $access_manager, AccountInterface $account) {
    $this->menuLinkStorage = $entity_manager->getStorageController('menu_link');
    $this->accessManager = $access_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    if (!empty($attributes['node']) && $attributes['node'] instanceof NodeInterface && !empty($attributes['node']->book)) {
      $mlids = array();
      $links = array($this->l($this->t('Home'), '<front>'));
      $book = $attributes['node']->book;
      $depth = 1;
      // We skip the current node.
      while (!empty($book['p' . ($depth + 1)])) {
        $mlids[] = $book['p' . $depth];
        $depth++;
      }
      $menu_links = $this->menuLinkStorage->loadMultiple($mlids);
      if (count($menu_links) > 0) {
        $depth = 1;
        while (!empty($book['p' . ($depth + 1)])) {
          if (!empty($menu_links[$book['p' . $depth]]) && ($menu_link = $menu_links[$book['p' . $depth]])) {
            if ($this->accessManager->checkNamedRoute($menu_link->route_name, $menu_link->route_parameters, $this->account)) {
              $links[] = $this->l($menu_link->label(), $menu_link->route_name, $menu_link->route_parameters, $menu_link->options);
            }
          }
          $depth++;
        }
      }
      return $links;
    }
  }

}
