<?php

/**
 * @file
 * Definition of views_handler_field_user_link_edit.
 */

/**
 * Field handler to present a link to user edit.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_user_link_edit extends views_handler_field_user_link {
  function render_link($data, $values) {
    // Build a pseudo account object to be able to check the access.
    $account = new stdClass();
    $account->uid = $data;

    if ($data && user_edit_access($account)) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('edit');

      $this->options['alter']['path'] = "user/$data/edit";
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }
}
