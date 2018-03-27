<?php

namespace Drupal\comment\Plugin\views\field;

<<<<<<< HEAD
use Drupal\views\Plugin\views\field\BulkForm;
=======
use Drupal\system\Plugin\views\field\BulkForm;
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

/**
 * Defines a comment operations bulk form element.
 *
 * @ViewsField("comment_bulk_form")
 */
class CommentBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('Select one or more comments to perform the update on.');
  }

}
