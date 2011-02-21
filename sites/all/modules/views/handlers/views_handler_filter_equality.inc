<?php
// $Id: views_handler_filter_equality.inc,v 1.2 2008/09/10 01:08:06 merlinofchaos Exp $
/**
 * Simple filter to handle equal to / not equal to filters
 */
class views_handler_filter_equality extends views_handler_filter {
  // exposed filter options
  var $no_single = TRUE;

  /**
   * Provide simple equality operator
   */
  function operator_options() {
    return array(
      '=' => t('Is equal to'),
      '!=' => t('Is not equal to'),
    );
  }

  /**
   * Provide a simple textfield for equality
   */
  function value_form(&$form, &$form_state) {
    $form['value'] = array(
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    );

    if (!empty($form_state['exposed'])) {
      $identifier = $this->options['expose']['identifier'];
      if (!isset($form_state['input'][$identifier])) {
        $form_state['input'][$identifier] = $this->value;
      }
    }
  }
}

