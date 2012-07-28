<?php

/**
 * @file
 * Definition of views_handler_argument_locale_group.
 */

/**
 * Argument handler to accept a language.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_locale_group extends views_handler_argument {
  function construct() {
    parent::construct('group');
  }

  /**
   * Override the behavior of summary_name(). Get the user friendly version
   * of the group.
   */
  function summary_name($data) {
    return $this->locale_group($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version
   * of the language.
   */
  function title() {
    return $this->locale_group($this->argument);
  }

  function locale_group($group) {
    $groups = module_invoke_all('locale', 'groups');
    // Sort the list.
    asort($groups);
    return isset($groups[$group]) ? $groups[$group] : t('Unknown group');
  }
}
