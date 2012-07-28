<?php

/**
 * @file
 * Definition of views_handler_field_file_extension.
 */

/**
 * Returns a pure file extension of the file, for example 'module'.
 * @ingroup views_field_handlers
 */
class views_handler_field_file_extension extends views_handler_field {
  function render($values) {
    $value = $this->get_value($values);
    if (preg_match('/\.([^\.]+)$/', $value, $match)) {
      return $this->sanitize_value($match[1]);
    }
  }
}
