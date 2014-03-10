<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\filter\NodeComment.
 */

namespace Drupal\comment\Plugin\views\filter;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter based on comment node status.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("node_comment")
 */
class NodeComment extends InOperator {

  public function getValueOptions() {
    $this->value_options = array(
      CommentItemInterface::HIDDEN => t('Hidden'),
      CommentItemInterface::CLOSED => t('Closed'),
      CommentItemInterface::OPEN => t('Open'),
    );
  }

}
