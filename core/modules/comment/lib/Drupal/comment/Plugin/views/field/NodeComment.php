<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\field\NodeComment.
 */

namespace Drupal\comment\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\Component\Annotation\PluginID;

/**
 * Display node comment status.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_comment")
 */
class NodeComment extends FieldPluginBase {

  public function render($values) {
    $value = $this->getValue($values);
    switch ($value) {
      case COMMENT_NODE_HIDDEN:
      default:
        return t('Hidden');
      case COMMENT_NODE_CLOSED:
        return t('Closed');
      case COMMENT_NODE_OPEN:
        return t('Open');
    }
  }

}
