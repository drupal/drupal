<?php
// $Id: views_handler_field_user_link_delete.inc,v 1.1.6.1 2010/11/18 07:29:03 dereine Exp $
/**
 * Field handler to present a link to user delete.
 */
class views_handler_field_user_link_delete extends views_handler_field_user_link {
  // An example of field level access control.
  function access() {
    return user_access('administer users');
  }

  function render_link($data, $values) {
    $uid = $values->{$this->aliases['uid']};
    $this->options['alter']['make_link'] = TRUE;

    $text = !empty($this->options['text']) ? $this->options['text'] : t('delete');

    $this->options['alter']['path'] = "user/$uid/delete";
    $this->options['alter']['query'] = drupal_get_destination();

    return $text;
  }
}

