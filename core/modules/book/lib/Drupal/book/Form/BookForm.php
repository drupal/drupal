<?php
/**
 * @file
 * Contains \Drupal\book\Form\BookForm.
 */

namespace Drupal\book\Form;

use Drupal\Core\Entity\EntityInterface;

/**
 * Temporary form controller for book module.
 */
class BookForm {

  /**
   * Wraps book_remove_form().
   *
   * @todo Remove book_remove_form().
   */
  public function remove(EntityInterface $node) {
    module_load_include('pages.inc', 'book');
    return drupal_get_form('book_remove_form', $node);
  }

}
