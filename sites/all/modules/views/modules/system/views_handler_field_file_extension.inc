<?php
// $Id: views_handler_field_file_extension.inc,v 1.1.4.2 2010/10/16 09:06:51 dereine Exp $
/**
 * @file
 * Returns a pure file extension of the file, for example 'module'.
 */

class views_handler_field_file_extension extends views_handler_field {
  function render($values) {
    $value = $values->{$this->field_alias};
    if (preg_match('/\.([^\.]+)$/', $value, $match)) {
      return check_plain($match[1]);
    }
  }
}
