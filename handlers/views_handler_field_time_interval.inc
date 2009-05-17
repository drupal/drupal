<?php

/**
 * @file
 * Definition of views_handler_field_time_interval.
 */

/**
 * A handler to provide proper displays for time intervals.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_time_interval extends views_handler_field {
  function option_definition() {
    $options = parent::option_definition();

    $options['granularity'] = array('default' => 2);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'textfield',
      '#title' => t('Granularity'),
      '#description' => t('How many different units to display in the string.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  function render($values) {
    $value = $values->{$this->field_alias};
    return format_interval($value, isset($this->options['granularity']) ? $this->options['granularity'] : 2);
  }
}
