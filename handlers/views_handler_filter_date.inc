<?php

/**
 * @file
 * Definition of views_handler_filter_date.
 */

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_date extends views_handler_filter_numeric {
  function option_definition() {
    $options = parent::option_definition();

    // value is already set up properly, we're just adding our new field to it.
    $options['value']['contains']['type']['default'] = 'date';

    return $options;
  }

  /**
   * Add a type selector to the value form
   */
  function value_form(&$form, &$form_state) {
    if (empty($form_state['exposed'])) {
      $form['value']['type'] = array(
        '#type' => 'radios',
        '#title' => t('Value type'),
        '#options' => array(
          'date' => t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.'),
          'offset' => t('An offset from the current time such as "!example1" or "!example2"', array('!example1' => '+1 day', '!example2' => '-2 hours -30 minutes')),
        ),
        '#default_value' => !empty($this->value['type']) ? $this->value['type'] : 'date',
      );
    }
    parent::value_form($form, $form_state);
  }

  function options_validate(&$form, &$form_state) {
    parent::options_validate($form, $form_state);

    if (!empty($this->options['exposed']) && empty($form_state['values']['options']['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $this->validate_valid_time($form['value'], $form_state['values']['options']['operator'], $form_state['values']['options']['value']);
  }

  function exposed_validate(&$form, &$form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $value = &$form_state['values'][$this->options['expose']['identifier']];
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $form_state['values'][$this->options['expose']['operator_id']];
    }
    else {
      $operator = $this->operator;
    }

    $this->validate_valid_time($this->options['expose']['identifier'], $operator, $value);

  }

  /**
   * Validate that the time values convert to something usable.
   */
  function validate_valid_time(&$form, $operator, $value) {
    $operators = $this->operators();

    if ($operators[$operator]['values'] == 1) {
      $convert = strtotime($value['value']);
      if (!empty($form['value']) && ($convert == -1 || $convert === FALSE)) {
        form_error($form['value'], t('Invalid date format.'));
      }
    }
    elseif ($operators[$operator]['values'] == 2) {
      $min = strtotime($value['min']);
      if ($min == -1 || $min === FALSE) {
        form_error($form['min'], t('Invalid date format.'));
      }
      $max = strtotime($value['max']);
      if ($max == -1 || $max === FALSE) {
        form_error($form['max'], t('Invalid date format.'));
      }
    }
  }

  function accept_exposed_input($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // Store this because it will get overwritten.
    $type = $this->value['type'];
    $rc = parent::accept_exposed_input($input);

    // Don't filter if value(s) are empty.
    $operators = $this->operators();
    if (!empty($this->options['expose']['use_operator']) && !empty($this->options['expose']['operator_id'])) {
      $operator = $input[$this->options['expose']['operator_id']];
    }
    else {
      $operator = $this->operator;
    }

    if ($operators[$operator]['values'] == 1) {
      if ($this->value['value'] == '') {
        return FALSE;
      }
    }
    else {
      if ($this->value['min'] == '' || $this->value['max'] == '') {
        return FALSE;
      }
    }

    // restore what got overwritten by the parent.
    $this->value['type'] = $type;
    return $rc;
  }

  function op_between($field) {
    $a = intval(strtotime($this->value['min'], 0));
    $b = intval(strtotime($this->value['max'], 0));

    if ($this->value['type'] == 'offset') {
      $a = '***CURRENT_TIME***' . sprintf('%+d', $a); // keep sign
      $b = '***CURRENT_TIME***' . sprintf('%+d', $b); // keep sign
    }
    // This is safe because we are manually scrubbing the values.
    // It is necessary to do it this way because $a and $b are formulas when using an offset.
    $operator = strtoupper($this->operator);
    $this->query->add_where_expression($this->options['group'], "$field $operator $a AND $b");
  }

  function op_simple($field) {
    $value = intval(strtotime($this->value['value'], 0));
    if (!empty($this->value['type']) && $this->value['type'] == 'offset') {
      $value = '***CURRENT_TIME***' . sprintf('%+d', $value); // keep sign
    }
    // This is safe because we are manually scrubbing the value.
    // It is necessary to do it this way because $value is a formula when using an offset.
    $this->query->add_where_expression($this->options['group'], "$field $this->operator $value");
  }
}
