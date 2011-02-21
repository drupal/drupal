<?php
// $Id: views_handler_field_locale_language.inc,v 1.1 2009/02/20 23:02:09 merlinofchaos Exp $

/**
 * Field handler to translate a language into its readable form.
 */
class views_handler_field_locale_language extends views_handler_field {
  function render($values) {
    $languages = language_list();
    return isset($languages[$values->{$this->field_alias}]) ? $languages[$values->{$this->field_alias}]->name : '';
  }
}
