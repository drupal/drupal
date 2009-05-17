<?php

/**
 * @file
 * Definition of views_handler_field_comment_link_reply.
 */

/**
 * Field handler to present a link to delete a node.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment_link_reply extends views_handler_field_comment_link {
  function access() {
    //check for permission to reply to comments
    return user_access('post comments');
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('reply');
    $nid =  $this->get_value($values, 'nid');
    $cid =  $this->get_value($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/" . $nid . '/' . $cid;

    return $text;
  }
}
