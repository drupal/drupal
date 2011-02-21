<?php
// $Id: views_handler_field_user_picture.inc,v 1.1.6.5 2010/12/16 18:23:42 dereine Exp $

/**
 * Field handler to provide simple renderer that allows using a themed user link
 */
class views_handler_field_user_picture extends views_handler_field {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = 'uid';
    $this->additional_fields['name'] = 'name';
    $this->additional_fields['mail'] = 'mail';   
  }

  function element_type($none_supported = FALSE, $default_empty = FALSE) {
    return 'div';
  }

  function render($values) {
    // Fake an account object.
    $account = new stdClass();
    $account->uid = $values->{$this->aliases['uid']};
    $account->name = $values->{$this->aliases['name']};
    $account->mail = $values->{$this->aliases['mail']};
    $account->picture = $values->{$this->field_alias};

    return theme('user_picture', array('account' => $account));
  }
}
