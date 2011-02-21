<?php
// $Id: views_handler_field_comment_link_reply.inc,v 1.1.6.1 2010/11/18 07:29:03 dereine Exp $

/**
 * Field handler to present a link to delete a node.
 */
class views_handler_field_comment_link_reply extends views_handler_field_comment_link {
  function access() {
    //check for permission to reply to comments
    return user_access('post comments');
  }

  function render_link($data, $values) {
    $text = !empty($this->options['text']) ? $this->options['text'] : t('reply');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/reply/" . $values->{$this->aliases['nid']} . '/' . $values->{$this->aliases['cid']};

    return $text;
  }
}
