<?php

namespace Drupal\book;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Provides a breadcrumb builder for nodes in a book.
 */
class BookBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

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
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(EntityManagerInterface $entity_manager, AccountInterface $account) {
    $this->nodeStorage = $entity_manager->getStorage('node');
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $node = $route_match->getParameter('node');
    return $node instanceof NodeInterface && !empty($node->book);
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $book_nids = [];
    $breadcrumb = new Breadcrumb();

    $links = [Link::createFromRoute($this->t('Home'), '<front>')];
    $book = $route_match->getParameter('node')->book;
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
          $access = $parent_book->access('view', $this->account, TRUE);
          $breadcrumb->addCacheableDependency($access);
          if ($access->isAllowed()) {
            $breadcrumb->addCacheableDependency($parent_book);
            $links[] = Link::createFromRoute($parent_book->label(), 'entity.node.canonical', ['node' => $parent_book->id()]);
          }
        }
        $depth++;
      }
    }
    $breadcrumb->setLinks($links);
    $breadcrumb->addCacheContexts(['route.book_navigation']);
    return $breadcrumb;
  }

}
