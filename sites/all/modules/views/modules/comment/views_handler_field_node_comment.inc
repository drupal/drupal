<?php
// $Id: views_handler_field_node_comment.inc,v 1.1.6.1 2010/12/19 10:31:18 dereine Exp $

/**
 * Display node comment status
 */
class views_handler_field_node_comment extends views_handler_field {
  function render($values) {
    switch ($values->{$this->field_alias}) {
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
