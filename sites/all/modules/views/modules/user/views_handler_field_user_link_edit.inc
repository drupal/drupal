<?php
// $Id: views_handler_field_user_link_edit.inc,v 1.1.6.2 2010/11/18 07:29:03 dereine Exp $
/**
 * Field handler to present a link to user edit.
 */
class views_handler_field_user_link_edit extends views_handler_field_user_link {

  function render_link($data, $values) {
    $uid = $values->{$this->aliases['uid']};
    // Build a pseudo account object to be able to check the access.
    $account = new stdClass();
    $account->uid = $uid;

    if ($uid && user_edit_access($account)) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

      $this->options['alter']['path'] = "user/$uid/edit";
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }
}

