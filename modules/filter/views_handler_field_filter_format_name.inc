<?php

/**
 * @file
 * Definition of views_handler_field_filter_format_name.
 */

/**
 * Field handler to output the name of an input format.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_filter_format_name extends views_handler_field {
  function construct() {
    parent::construct();
    // Be explicit about the table we are using.
    $this->additional_fields['name'] = array('table' => 'filter_formats', 'field' => 'name');
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $format_name = $this->get_value($values, 'name');
    if (!$format_name) {
      // Default or invalid input format.
      // filter_formats() will reliably return the default format even if the
      // current user is unprivileged.
      $format = filter_formats(filter_default_format());
      return $this->sanitize_value($format->name);
    }
    return $this->sanitize_value($format_name);
  }
}
