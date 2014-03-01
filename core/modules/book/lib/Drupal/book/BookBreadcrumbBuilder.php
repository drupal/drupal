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
   * The node storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $nodeStorage;

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
    $this->nodeStorage = $entity_manager->getStorageController('node');
    $this->accessManager = $access_manager;
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(array $attributes) {
    return !empty($attributes['node'])
    && ($attributes['node'] instanceof NodeInterface)
    && !empty($attributes['node']->book);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $attributes) {
    $book_nids = array();
    $links = array($this->l($this->t('Home'), '<front>'));
    $book = $attributes['node']->book;
    $depth = 1;
    // We skip the current node.
    while (!empty($book['p' . ($depth + 1)])) {
      $book_nids[] = $book['p' . $depth];
      $depth++;
    }
    $parent_books = $this->nodeStorage->loadMultiple($book_nids);
    if (count($parent_books) > 0) {
      $depth = 1;
      while (!empty($book['p' . ($depth + 1)])) {
        if (!empty($parent_books[$book['p' . $depth]]) && ($parent_book = $parent_books[$book['p' . $depth]])) {
          if ($parent_book->access('view', $this->account)) {
            $links[] = $this->l($parent_book->label(), 'node.view', array('node' => $parent_book->id()));
          }
        }
        $depth++;
      }
    }
    return $links;
  }

}
