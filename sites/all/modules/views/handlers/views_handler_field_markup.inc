<?php
// $Id: views_handler_field_markup.inc,v 1.3.6.5 2010/12/16 18:23:42 dereine Exp $

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
    $value = $values->{$this->field_alias};
    $format = is_array($this->format) ? $values->{$this->aliases['format']} : $this->format;
    if ($value) {
      $value = str_replace('<!--break-->', '', $value);
      return check_markup($value, $format, '');
    }
  }

  function element_type($none_supported = FALSE, $default_empty = FALSE) {
    if (isset($this->definition['element type'])) {
      return $this->definition['element type'];
    }

    return 'div';
  }
}
