<?php

/**
 * @file
 * Definition of views_handler_filter_in_operator.
 */

/**
 * Simple filter to handle matching of multiple options selectable via checkboxes
 *
 * Definition items:
 * - options callback: The function to call in order to generate the value options. If omitted, the options 'Yes' and 'No' will be used.
 * - options arguments: An array of arguments to pass to the options callback.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_in_operator extends views_handler_filter {
  var $value_form_type = 'checkboxes';

  /**
   * @var array
   * Stores all operations which are available on the form.
   */
  var $value_options = NULL;

  function construct() {
    parent::construct();
    $this->value_title = t('Options');
    $this->value_options = NULL;
  }

  /**
   * Child classes should be used to override this function and set the
   * 'value options', unless 'options callback' is defined as a valid function
   * or static public method to generate these values.
   *
   * This can use a guard to be used to reduce database hits as much as
   * possible.
   *
   * @return
   *   Return the stored values in $this->value_options if someone expects it.
   */
  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments']) && is_array($this->definition['options arguments'])) {
        $this->value_options = call_user_func_array($this->definition['options callback'], $this->definition['options arguments']);
      }
      else {
        $this->value_options = call_user_func($this->definition['options callback']);
      }
    }
    else {
      $this->value_options = array(t('Yes'), t('No'));
    }

    return $this->value_options;
  }

  function expose_options() {
    parent::expose_options();
    $this->options['expose']['reduce'] = FALSE;
  }

  function expose_form(&$form, &$form_state) {
    parent::expose_form($form, $form_state);
    $form['expose']['reduce'] = array(
      '#type' => 'checkbox',
      '#title' => t('Limit list to selected items'),
      '#description' => t('If checked, the only items presented to the user will be the ones selected here.'),
      '#default_value' => !empty($this->options['expose']['reduce']), // safety
    );
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['operator']['default'] = 'in';
    $options['value']['default'] = array();
    $options['expose']['contains']['reduce'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  /**
   * This kind of construct makes it relatively easy for a child class
   * to add or remove functionality by overriding this function and
   * adding/removing items from this array.
   */
  function operators() {
    $operators = array(
      'in' => array(
        'title' => t('Is one of'),
        'short' => t('in'),
        'short_single' => t('='),
        'method' => 'op_simple',
        'values' => 1,
      ),
      'not in' => array(
        'title' => t('Is not one of'),
        'short' => t('not in'),
        'short_single' => t('<>'),
        'method' => 'op_simple',
        'values' => 1,
      ),
    );
    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += array(
        'empty' => array(
          'title' => t('Is empty (NULL)'),
          'method' => 'op_empty',
          'short' => t('empty'),
          'values' => 0,
        ),
        'not empty' => array(
          'title' => t('Is not empty (NOT NULL)'),
          'method' => 'op_empty',
          'short' => t('not empty'),
          'values' => 0,
        ),
      );
    }

    return $operators;
  }

  /**
   * Build strings from the operators() for 'select' options
   */
  function operator_options($which = 'title') {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  function operator_values($values = 1) {
    $options = array();
    foreach ($this->operators() as $id => $info) {
      if (isset($info['values']) && $info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
  }

  function value_form(&$form, &$form_state) {
    $form['value'] = array();
    $options = array();

    if (empty($form_state['exposed'])) {
      // Add a select all option to the value form.
      $options = array('all' => t('Select all'));
    }

    $this->get_value_options();
    $options += $this->value_options;
    $default_value = (array) $this->value;

    $which = 'all';
    if (!empty($form['operator'])) {
      $source = ($form['operator']['#type'] == 'radios') ? 'radio:options[operator]' : 'edit-options-operator';
    }
    if (!empty($form_state['exposed'])) {
      $identifier = $this->options['expose']['identifier'];

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = in_array($this->operator, $this->operator_values(1)) ? 'value' : 'none';
      }
      else {
        $source = 'edit-' . drupal_html_id($this->options['expose']['operator_id']);
      }

      if (!empty($this->options['expose']['reduce'])) {
        $options = $this->reduce_value_options();

        if (!empty($this->options['expose']['multiple']) && empty($this->options['expose']['required'])) {
          $default_value = array();
        }
      }

      if (empty($this->options['expose']['multiple'])) {
        if (empty($this->options['expose']['required']) && (empty($default_value) || !empty($this->options['expose']['reduce']))) {
          $default_value = 'All';
        }
        elseif (empty($default_value)) {
          $keys = array_keys($options);
          $default_value = array_shift($keys);
        }
        else {
          $copy = $default_value;
          $default_value = array_shift($copy);
        }
      }
    }

    if ($which == 'all' || $which == 'value') {
      $form['value'] = array(
        '#type' => $this->value_form_type,
        '#title' => $this->value_title,
        '#options' => $options,
        '#default_value' => $default_value,
        // These are only valid for 'select' type, but do no harm to checkboxes.
        '#multiple' => TRUE,
        '#size' => count($options) > 8 ? 8 : count($options),
      );
      if (!empty($form_state['exposed']) && !isset($form_state['input'][$identifier])) {
        $form_state['input'][$identifier] = $default_value;
      }

      if ($which == 'all') {
        if (empty($form_state['exposed']) && (in_array($this->value_form_type, array('checkbox', 'checkboxes', 'radios', 'select')))) {
          $form['value']['#prefix'] = '<div id="edit-options-value-wrapper">';
          $form['value']['#suffix'] = '</div>';
        }
        $form['value']['#dependency'] = array($source => $this->operator_values(1));
      }
    }
  }

  /**
   * When using exposed filters, we may be required to reduce the set.
   */
  function reduce_value_options($input = NULL) {
    if (!isset($input)) {
      $input = $this->value_options;
    }

    // Because options may be an array of strings, or an array of mixed arrays
    // and strings (optgroups) or an array of objects, we have to
    // step through and handle each one individually.
    $options = array();
    foreach ($input as $id => $option) {
      if (is_array($option)) {
        $options[$id] = $this->reduce_value_options($option);
        continue;
      }
      elseif (is_object($option)) {
        $keys = array_keys($option->option);
        $key = array_shift($keys);
        if (isset($this->options['value'][$key])) {
          $options[$id] = $option;
        }
      }
      elseif (isset($this->options['value'][$id])) {
        $options[$id] = $option;
      }
    }
    return $options;
  }

  function accept_exposed_input($input) {
    // A very special override because the All state for this type of
    // filter could have a default:
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    // If this is non-multiple and non-required, then this filter will
    // participate, but using the default settings, *if* 'limit is true.
    if (empty($this->options['expose']['multiple']) && empty($this->options['expose']['required']) && !empty($this->options['expose']['limit'])) {
      $identifier = $this->options['expose']['identifier'];
      if ($input[$identifier] == 'All') {
        return TRUE;
      }
    }

    return parent::accept_exposed_input($input);
  }

  function value_submit($form, &$form_state) {
    // Drupal's FAPI system automatically puts '0' in for any checkbox that
    // was not set, and the key to the checkbox if it is set.
    // Unfortunately, this means that if the key to that checkbox is 0,
    // we are unable to tell if that checkbox was set or not.

    // Luckily, the '#value' on the checkboxes form actually contains
    // *only* a list of checkboxes that were set, and we can use that
    // instead.

    $form_state['values']['options']['value'] = $form['value']['#value'];
  }

  function admin_summary() {
    if (!empty($this->options['exposed'])) {
      return t('exposed');
    }
    $info = $this->operators();

    $this->get_value_options();

    if (!is_array($this->value)) {
      return;
    }

    $operator = check_plain($info[$this->operator]['short']);
    $values = '';
    if (in_array($this->operator, $this->operator_values(1))) {
      // Remove every element which is not known.
      foreach ($this->value as $value) {
        if (!isset($this->value_options[$value])) {
          unset($this->value[$value]);
        }
      }
      // Choose different kind of ouput for 0, a single and multiple values.
      if (count($this->value) == 0) {
        $values = t('Unknown');
      }
      else if (count($this->value) == 1) {
        // If any, use the 'single' short name of the operator instead.
        if (isset($info[$this->operator]['short_single'])) {
          $operator = check_plain($info[$this->operator]['short_single']);
        }

        $keys = $this->value;
        $value = array_shift($keys);
        if (isset($this->value_options[$value])) {
          $values = check_plain($this->value_options[$value]);
        }
        else {
          $values = '';
        }
      }
      else {
        foreach ($this->value as $value) {
          if ($values !== '') {
            $values .= ', ';
          }
          if (drupal_strlen($values) > 8) {
            $values .= '...';
            break;
          }
          if (isset($this->value_options[$value])) {
            $values .= check_plain($this->value_options[$value]);
          }
        }
      }
    }

    return $operator . (($values !== '') ? ' ' . $values : '');
  }

  function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}();
    }
  }

  function op_simple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensure_my_table();

    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field", array_values($this->value), $this->operator);
  }

  function op_empty() {
    $this->ensure_my_table();
    if ($this->operator == 'empty') {
      $operator = "IS NULL";
    }
    else {
      $operator = "IS NOT NULL";
    }

    $this->query->add_where($this->options['group'], "$this->table_alias.$this->real_field", NULL, $operator);
  }

  function validate() {
    $this->get_value_options();
    $errors = array();

    // If the operator is an operator which doesn't require a value, there is
    // no need for additional validation.
    if (in_array($this->operator, $this->operator_values(0))) {
      return array();
    }

    if (!in_array($this->operator, $this->operator_values(1))) {
      $errors[] = t('The operator is invalid on filter: @filter.', array('@filter' => $this->ui_name(TRUE)));
    }
    if (is_array($this->value)) {
      if (!isset($this->value_options)) {
        // Don't validate if there are none value options provided, for example for special handlers.
        return $errors;
      }
      if ($this->options['exposed'] && !$this->options['expose']['required'] && empty($this->value)) {
        // Don't validate if the field is exposed and no default value is provided.
        return $errors;
      }

      // Some filter_in_operator usage uses optgroups forms, so flatten it.
      $flat_options = form_options_flatten($this->value_options, TRUE);

      // Remove every element which is not known.
      foreach ($this->value as $value) {
        if (!isset($flat_options[$value])) {
          unset($this->value[$value]);
        }
      }
      // Choose different kind of ouput for 0, a single and multiple values.
      if (count($this->value) == 0) {
        $errors[] = t('No valid values found on filter: @filter.', array('@filter' => $this->ui_name(TRUE)));
      }
    }
    elseif (!empty($this->value) && ($this->operator == 'in' || $this->operator == 'not in')) {
      $errors[] = t('The value @value is not an array for @operator on filter: @filter', array('@value' => views_var_export($this->value), '@operator' => $this->operator, '@filter' => $this->ui_name(TRUE)));
    }
    return $errors;
  }
}
