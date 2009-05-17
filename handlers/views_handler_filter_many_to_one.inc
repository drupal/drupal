<?php

/**
 * @file
 * Definition of views_handler_filter_many_to_one.
 */

/**
 * Complex filter to handle filtering for many to one relationships,
 * such as terms (many terms per node) or roles (many roles per user).
 *
 * The construct method needs to be overridden to provide a list of options;
 * alternately, the value_form and admin_summary methods need to be overriden
 * to provide something that isn't just a select list.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_many_to_one extends views_handler_filter_in_operator {
  /**
   * @var views_many_to_one_helper
   *
   * Stores the Helper object which handles the many_to_one complexity.
   */
  var $helper = NULL;

  function init(&$view, &$options) {
    parent::init($view, $options);
    $this->helper = new views_many_to_one_helper($this);
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['operator']['default'] = 'or';
    $options['value']['default'] = array();

    if (isset($this->helper)) {
      $this->helper->option_definition($options);
    }
    else {
      $helper = new views_many_to_one_helper($this);
      $helper->option_definition($options);
    }

    return $options;
  }

  function operators() {
    $operators = array(
      'or' => array(
        'title' => t('Is one of'),
        'short' => t('or'),
        'short_single' => t('='),
        'method' => 'op_helper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ),
      'and' => array(
        'title' => t('Is all of'),
        'short' => t('and'),
        'short_single' => t('='),
        'method' => 'op_helper',
        'values' => 1,
        'ensure_my_table' => 'helper',
      ),
      'not' => array(
        'title' => t('Is none of'),
        'short' => t('not'),
        'short_single' => t('<>'),
        'method' => 'op_helper',
        'values' => 1,
        'ensure_my_table' => 'helper',
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

  var $value_form_type = 'select';
  function value_form(&$form, &$form_state) {
    parent::value_form($form, $form_state);

    if (empty($form_state['exposed'])) {
      $this->helper->options_form($form, $form_state);
    }
  }

  /**
   * Override ensure_my_table so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  function ensure_my_table() {
    // Defer to helper if the operator specifies it.
    $info = $this->operators();
    if (isset($info[$this->operator]['ensure_my_table']) && $info[$this->operator]['ensure_my_table'] == 'helper') {
      return $this->helper->ensure_my_table();
    }

    return parent::ensure_my_table();
  }

  function op_helper() {
    if (empty($this->value)) {
      return;
    }
    $this->helper->add_filter();
  }
}
