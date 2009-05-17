<?php

/**
 * @file
 * Definition of views_handler_argument_field_list.
 */

/**
 * Argument handler for list field to show the human readable name in the
 * summary.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_field_list extends views_handler_argument_numeric {
  /**
   * Stores the allowed values of this field.
   *
   * @var array
   */
  var $allowed_values = NULL;

  function init(&$view, &$options) {
    parent::init($view, $options);
    $field = field_info_field($this->definition['field_name']);
    $this->allowed_values = list_allowed_values($field);
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['summary']['contains']['human'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['summary']['human'] = array(
      '#title' => t('Display list value as human readable'),
      '#type' => 'checkbox',
      '#default_value' => $this->options['summary']['human'],
      '#dependency' => array('radio:options[default_action]' => array('summary')),
    );
  }


  function summary_name($data) {
    $value = $data->{$this->name_alias};
    // If the list element has a human readable name show it,
    if (isset($this->allowed_values[$value]) && !empty($this->options['summary']['human'])) {
      return field_filter_xss($this->allowed_values[$value]);
    }
    // else fallback to the key.
    else {
      return check_plain($value);
    }
  }
}
