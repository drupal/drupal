<?php

/**
 * @file
 * Definition of views_handler_field_user_language.
 */

/**
 * Views field handler for user language.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_user_language extends views_handler_field_user {

  function render_link($data, $values) {
    $uid = $this->get_value($values, 'uid');
    if (!empty($this->options['link_to_user'])) {
      $uid = $this->get_value($values, 'uid');
      if (user_access('access user profiles') && $uid) {
        $this->options['alter']['make_link'] = TRUE;
        $this->options['alter']['path'] = 'user/' . $uid;
      }
    }
    if (empty($data)) {
      $lang = language_default();
    }
    else {
      $lang = language_list();
      $lang = $lang[$data];
    }

    return $this->sanitize_value($lang->name);
  }

  function render($values) {
    $value = $this->get_value($values);
    return $this->render_link($this->sanitize_value($value), $values);
  }
}
