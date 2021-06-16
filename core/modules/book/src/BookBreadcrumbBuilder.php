<?php

namespace Drupal\book;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|null
   */
  protected $languageManager;

  /**
   * Constructs the BookBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface|null $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface|null $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountInterface $account, EntityRepositoryInterface $entity_repository = NULL, LanguageManagerInterface $language_manager = NULL) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->account = $account;
    if (!$entity_repository) {
      @trigger_error('The entity.repository service must be passed to ' . __NAMESPACE__ . '\BookBreadcrumbBuilder::__construct(). It was added in drupal:9.2.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $entity_repository = \Drupal::service('entity.repository');
    }
    if (!$language_manager) {
      @trigger_error('The language_manager service must be passed to ' . __NAMESPACE__ . '\BookBreadcrumbBuilder::__construct(). It was added in drupal:9.2.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $language_manager = \Drupal::service('language_manager');
    }
    $this->entityRepository = $entity_repository;
    $this->languageManager = $language_manager;
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

    $links = [Link::createFromRoute($this->t('Home'), '<front>', [], [
      'language' => $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT),
    ]),
    ];
    $breadcrumb->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
    $book = $route_match->getParameter('node')->book;
    $depth = 1;
    // We skip the current node.
    while (!empty($book['p' . ($depth + 1)])) {
      $book_nids[] = $book['p' . $depth];
      $depth++;
    }
    /** @var \Drupal\node\NodeInterface[] $parent_books */
    $parent_books = $this->nodeStorage->loadMultiple($book_nids);
    $parent_books = array_map([$this->entityRepository, 'getTranslationFromContext'], $parent_books);
    if (count($parent_books) > 0) {
      $depth = 1;
      while (!empty($book['p' . ($depth + 1)])) {
        if (!empty($parent_books[$book['p' . $depth]]) && ($parent_book = $parent_books[$book['p' . $depth]])) {
          $access = $parent_book->access('view', $this->account, TRUE);
          $breadcrumb->addCacheableDependency($access);
          if ($access->isAllowed()) {
            $breadcrumb->addCacheableDependency($parent_book);
            $links[] = $parent_book->toLink();
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
