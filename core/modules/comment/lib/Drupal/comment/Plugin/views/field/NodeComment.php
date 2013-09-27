<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeComment.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;
use Drupal\views\ResultRow;

/**
 * Display node comment status.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_comment")
 */
class NodeComment extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);
    switch ($value) {
      case COMMENT_HIDDEN:
      default:
        return t('Hidden');
      case COMMENT_CLOSED:
        return t('Closed');
      case COMMENT_OPEN:
        return t('Open');
    }
  }

}
