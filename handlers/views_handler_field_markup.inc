<?php

/**
 * @file
 * Definition of views_handler_field_markup.
 */

/**
 * A handler to run a field through check_markup, using a companion
 * format field.
 *
 * - format: (REQUIRED) Either a string format id to use for this field or an
 *           array('field' => {$field}) where $field is the field in this table
 *           used to control the format such as the 'format' field in the node,
 *           which goes with the 'body' field.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_markup extends views_handler_field {
  /**
   * Constructor; calls to base object constructor.
   */
  function construct() {
    parent::construct();

    $this->format = $this->definition['format'];

    $this->additional_fields = array();
    if (is_array($this->format)) {
      $this->additional_fields['format'] = $this->format;
    }
  }

  function render($values) {
    $value = $this->get_value($values);
    if (is_array($this->format)) {
      $format = $this->get_value($values, 'format');
    }
    else {
      $format = $this->format;
    }
    if ($value) {
      $value = str_replace('<!--break-->', '', $value);
      return check_markup($value, $format, '');
    }
  }

  function element_type($none_supported = FALSE, $default_empty = FALSE, $inline = FALSE) {
    if ($inline) {
      return 'span';
    }

    if (isset($this->definition['element type'])) {
      return $this->definition['element type'];
    }

    return 'div';
  }
}
