<?php

/**
 * @file
 * Definition of views_handler_filter_locale_group.
 */

/**
 * Filter by locale group.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_locale_group extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Group');
      $groups = module_invoke_all('locale', 'groups');
      // Sort the list.
      asort($groups);
      $this->value_options = $groups;
    }
  }
}
