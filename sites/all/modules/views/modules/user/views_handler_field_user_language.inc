<?php
// $Id: views_handler_field_user_language.inc,v 1.1.6.1 2009/11/02 22:01:27 merlinofchaos Exp $
/**
 * @file
 *   Views field handler for userlanguage.
 */

class views_handler_field_user_language extends views_handler_field_user {

  function render_link($data, $values) {
    if (!empty($this->options['link_to_user']) && user_access('access user profiles') && $values->{$this->aliases['uid']}) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'user/' . $values->{$this->aliases['uid']};
    }
    if (empty($data)) {
      $lang = language_default();
    }
    else {
      $lang = language_list();
      $lang = $lang[$data];
    }

    return check_plain($lang->name);
  }

  function render($values) {
    return $this->render_link(check_plain($values->{$this->field_alias}), $values);
  }
}
