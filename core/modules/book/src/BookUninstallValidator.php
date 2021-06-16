<?php

namespace Drupal\book;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BookUninstallValidator.
   *
   * @param \Drupal\book\BookOutlineStorageInterface $book_outline_storage
   *   The book outline storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(BookOutlineStorageInterface $book_outline_storage, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->bookOutlineStorage = $book_outline_storage;
    $this->entityTypeManager = $entity_type_manager;
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
    $nodes = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'book')
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();
    return !empty($nodes);
  }

}
