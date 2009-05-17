<?php

/**
 * @file
 * Definition of views_handler_argument_numeric.
 */

/**
 * Basic argument handler for arguments that are numeric. Incorporates
 * break_phrase.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_numeric extends views_handler_argument {
  /**
   * The operator used for the query: or|and.
   * @var string
   */
  var $operator;

  /**
   * The actual value which is used for querying.
   * @var array
   */
  var $value;

  function option_definition() {
    $options = parent::option_definition();

    $options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);
    $options['not'] = array('default' => FALSE, 'bool' => TRUE);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // allow + for or, , for and
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple values'),
      '#description' => t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#fieldset' => 'more',
    );

    $form['not'] = array(
      '#type' => 'checkbox',
      '#title' => t('Exclude'),
      '#description' => t('If selected, the numbers entered for the filter will be excluded rather than limiting the view.'),
      '#default_value' => !empty($this->options['not']),
      '#fieldset' => 'more',
    );
  }

  function title() {
    if (!$this->argument) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    if (!empty($this->options['break_phrase'])) {
      views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    if ($this->value === array(-1)) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->title_query());
  }

  /**
   * Override for specific title lookups.
   * @return array
   *    Returns all titles, if it's just one title it's an array with one entry.
   */
  function title_query() {
    return $this->value;
  }

  function query($group_by = FALSE) {
    $this->ensure_my_table();

    if (!empty($this->options['break_phrase'])) {
      views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
    }

    $placeholder = $this->placeholder();
    $null_check = empty($this->options['not']) ? '' : "OR $this->table_alias.$this->real_field IS NULL";

    if (count($this->value) > 1) {
      $operator = empty($this->options['not']) ? 'IN' : 'NOT IN';
      $this->query->add_where_expression(0, "$this->table_alias.$this->real_field $operator($placeholder) $null_check", array($placeholder => $this->value));
    }
    else {
      $operator = empty($this->options['not']) ? '=' : '!=';
      $this->query->add_where_expression(0, "$this->table_alias.$this->real_field $operator $placeholder $null_check", array($placeholder => $this->argument));
    }
  }

  function get_sort_name() {
    return t('Numerical', array(), array('context' => 'Sort order'));
  }
}
