<?php

/**
 * @file
 * Definition of views_handler_field_node_comment.
 */

/**
 * Display node comment status.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_node_comment extends views_handler_field {
  function render($values) {
    $value = $this->get_value($values);
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
