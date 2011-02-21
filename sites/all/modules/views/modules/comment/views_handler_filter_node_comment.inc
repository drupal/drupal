<?php
// $Id: views_handler_filter_node_comment.inc,v 1.1.6.1 2010/12/19 10:31:18 dereine Exp $

/**
 * Filter based on comment node status
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
