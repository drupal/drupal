<?php

/**
 * @file
 * Definition of views_handler_filter_profile_selection.
 */

/**
 * Filter by a selection widget in the profile.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_profile_selection extends views_handler_filter_in_operator {
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();
    $all_options = profile_views_get_fields();
    $field = $all_options[$this->definition['fid']];

    $lines = preg_split("/[,\n\r]/", $field->options);
    foreach ($lines as $line) {
      if ($line = trim($line)) {
        $this->value_options[$line] = $line;
      }
    }
  }
}
