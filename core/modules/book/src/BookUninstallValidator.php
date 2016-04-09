<?php

namespace Drupal\book;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents book module from being uninstalled whilst any book nodes exist or
 * there are any book outline stored.
 */
class BookUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The book outline storage.
   *
   * @var \Drupal\book\BookOutlineStorageInterface
   */
  protected $bookOutlineStorage;

  /**
   * The entity query for node.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

  /**
   * Constructs a new BookUninstallValidator.
   *
   * @param \Drupal\book\BookOutlineStorageInterface $book_outline_storage
   *   The book outline storage.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(BookOutlineStorageInterface $book_outline_storage, QueryFactory $query_factory, TranslationInterface $string_translation) {
    $this->bookOutlineStorage = $book_outline_storage;
    $this->entityQuery = $query_factory->get('node');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'book') {
      if ($this->hasBookOutlines()) {
        $reasons[] = $this->t('To uninstall Book, delete all content that is part of a book');
      }
      else {
        // The book node type is provided by the Book module. Prevent uninstall
        // if there are any nodes of that type.
        if ($this->hasBookNodes()) {
          $reasons[] = $this->t('To uninstall Book, delete all content that has the Book content type');
        }
      }
    }
    return $reasons;
  }

  /**
   * Checks if there are any books in an outline.
   *
   * @return bool
   *   TRUE if there are books, FALSE if not.
   */
  protected function hasBookOutlines() {
    return $this->bookOutlineStorage->hasBooks();
  }

  /**
   * Determines if there is any book nodes or not.
   *
   * @return bool
   *   TRUE if there are book nodes, FALSE otherwise.
   */
  protected function hasBookNodes() {
    $nodes = $this->entityQuery
      ->condition('type', 'book')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

}
