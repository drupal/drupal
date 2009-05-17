<?php

/**
 * @file
 * Definition of views_handler_field_comment_link_approve.
 */

/**
 * Provides a comment approve link.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_comment_link_approve extends views_handler_field_comment_link {
  function access() {
    //needs permission to administer comments in general
    return user_access('administer comments');
  }

  function render_link($data, $values) {
    $status = $this->get_value($values, 'status');

    // Don't show an approve link on published nodes.
    if ($status == COMMENT_PUBLISHED) {
      return;
    }

    $text = !empty($this->options['text']) ? $this->options['text'] : t('approve');
    $cid =  $this->get_value($values, 'cid');

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "comment/" . $cid . "/approve";
    $this->options['alter']['query'] = drupal_get_destination() + array('token' => drupal_get_token("comment/$cid/approve"));

    return $text;
  }
}
