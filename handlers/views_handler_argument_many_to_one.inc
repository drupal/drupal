<?php

/**
 * @file
 * Definition of views_handler_argument_many_to_one.
 */

/**
 * An argument handler for use in fields that have a many to one relationship
 * with the table(s) to the left. This adds a bunch of options that are
 * reasonably common with this type of relationship.
 * Definition terms:
 * - numeric: If true, the field will be considered numeric. Probably should
 *   always be set TRUE as views_handler_argument_string has many to one
 *   capabilities.
 * - zero is null: If true, a 0 will be handled as empty, so for example
 *   a default argument can be provided or a summary can be shown.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_many_to_one extends views_handler_argument {
  function init(&$view, &$options) {
    parent::init($view, $options);
    $this->helper = new views_many_to_one_helper($this);

    // Ensure defaults for these, during summaries and stuff:
    $this->operator = 'or';
    $this->value = array();
  }

  function option_definition() {
    $options = parent::option_definition();

    if (!empty($this->definition['numeric'])) {
      $options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);
    }

    $options['add_table'] = array('default' => FALSE, 'bool' => TRUE);
    $options['require_value'] = array('default' => FALSE, 'bool' => TRUE);

    if (isset($this->helper)) {
      $this->helper->option_definition($options);
    }
    else {
      $helper = new views_many_to_one_helper($this);
      $helper->option_definition($options);
    }

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    // allow + for or, , for and
    if (!empty($this->definition['numeric'])) {
      $form['break_phrase'] = array(
        '#type' => 'checkbox',
        '#title' => t('Allow multiple values'),
        '#description' => t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
        '#default_value' => !empty($this->options['break_phrase']),
        '#fieldset' => 'more',
      );
    }

    $form['add_table'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple filter values to work together'),
      '#description' => t('If selected, multiple instances of this filter can work together, as though multiple values were supplied to the same filter. This setting is not compatible with the "Reduce duplicates" setting.'),
      '#default_value' => !empty($this->options['add_table']),
      '#fieldset' => 'more',
    );

    $form['require_value'] = array(
      '#type' => 'checkbox',
      '#title' => t('Do not display items with no value in summary'),
      '#default_value' => !empty($this->options['require_value']),
      '#fieldset' => 'more',
    );

    $this->helper->options_form($form, $form_state);
  }

  /**
   * Override ensure_my_table so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  function ensure_my_table() {
    $this->helper->ensure_my_table();
  }

  function query($group_by = FALSE) {
    $empty = FALSE;
    if (isset($this->definition['zero is null']) && $this->definition['zero is null']) {
      if (empty($this->argument)) {
        $empty = TRUE;
      }
    }
    else {
      if (!isset($this->argument)) {
        $empty = TRUE;
      }
    }
    if ($empty) {
      parent::ensure_my_table();
      $this->query->add_where(0, "$this->table_alias.$this->real_field", NULL, 'IS NULL');
      return;
    }

    if (!empty($this->options['break_phrase'])) {
      views_break_phrase($this->argument, $this);
    }
    else {
      $this->value = array($this->argument);
      $this->operator = 'or';
    }

    $this->helper->add_filter();
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

    // @todo -- both of these should check definition for alternate keywords.

    if (empty($this->value)) {
      return !empty($this->definition['empty field name']) ? $this->definition['empty field name'] : t('Uncategorized');
    }

    if ($this->value === array(-1)) {
      return !empty($this->definition['invalid input']) ? $this->definition['invalid input'] : t('Invalid input');
    }

    return implode($this->operator == 'or' ? ' + ' : ', ', $this->title_query());
  }

  function summary_query() {
    $field = $this->table . '.' . $this->field;
    $join = $this->get_join();

    if (!empty($this->options['require_value'])) {
      $join->type = 'INNER';
    }

    if (empty($this->options['add_table']) || empty($this->view->many_to_one_tables[$field])) {
      $this->table_alias = $this->query->ensure_table($this->table, $this->relationship, $join);
    }
    else {
      $this->table_alias = $this->helper->summary_join();
    }

    // Add the field.
    $this->base_alias = $this->query->add_field($this->table_alias, $this->real_field);

    $this->summary_name_field();

    return $this->summary_basics();
  }

  function summary_argument($data) {
    $value = $data->{$this->base_alias};
    if (empty($value)) {
      $value = 0;
    }

    return $value;
  }

  /**
   * Override for specific title lookups.
   */
  function title_query() {
    return $this->value;
  }
}
