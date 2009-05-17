<?php

/**
 * @file
 * Definition of views_handler_filter_node_comment.
 */

/**
 * Filter based on comment node status.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_node_comment extends views_handler_filter_in_operator {
  function get_value_options() {
    $this->value_options = array(
      COMMENT_NODE_HIDDEN => t('Hidden'),
      COMMENT_NODE_CLOSED => t('Closed'),
      COMMENT_NODE_OPEN => t('Open'),
    );
  }
}
