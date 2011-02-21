<?php
// $Id: views_handler_field_locale_group.inc,v 1.1 2009/02/20 23:02:09 merlinofchaos Exp $

/**
 * Field handler to translate a group into its readable form.
 */
class views_handler_field_locale_group extends views_handler_field {
  function render($values) {
    $groups = module_invoke_all('locale', 'groups');
    // Sort the list.
    asort($groups);
    return isset($groups[$values->{$this->field_alias}]) ? $groups[$values->{$this->field_alias}] : '';
  }
}
