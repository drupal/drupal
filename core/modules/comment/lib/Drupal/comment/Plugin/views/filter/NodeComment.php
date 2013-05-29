<?php

/**
 * @file
 * Definition of Drupal\comment\Plugin\views\filter\NodeComment.
 */

namespace Drupal\comment\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\PluginID;

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
      COMMENT_NODE_HIDDEN => t('Hidden'),
      COMMENT_NODE_CLOSED => t('Closed'),
      COMMENT_NODE_OPEN => t('Open'),
    );
  }

}
