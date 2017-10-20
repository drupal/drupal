<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\BulkForm;

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
