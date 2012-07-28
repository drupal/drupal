<?php

/**
 * @file
 * Definition of views_handler_field_file_status.
 */

/**
 * Field handler to translate a node type into its readable form.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_file_status extends views_handler_field {
  function render($values) {
    $value = $this->get_value($values);
    return _views_file_status($value);
  }
}
