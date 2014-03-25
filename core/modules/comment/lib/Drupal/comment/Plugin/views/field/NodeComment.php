<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeComment.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Display node comment status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_comment")
 */
class NodeComment extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    switch ($value) {
      case CommentItemInterface::HIDDEN:
      default:
        return t('Hidden');
      case CommentItemInterface::CLOSED:
        return t('Closed');
      case CommentItemInterface::OPEN:
        return t('Open');
    }
  }

}
