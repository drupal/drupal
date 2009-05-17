<?php

/**
 * @file
 * Definition of views_handler_argument_string.
 */

/**
 * Basic argument handler to implement string arguments that may have length
 * limits.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_string extends views_handler_argument {
  function init(&$view, &$options) {
    parent::init($view, $options);
    if (!empty($this->definition['many to one'])) {
      $this->helper = new views_many_to_one_helper($this);

      // Ensure defaults for these, during summaries and stuff:
      $this->operator = 'or';
      $this->value = array();
    }
  }

  function option_definition() {
    $options = parent::option_definition();

    $options['glossary'] = array('default' => FALSE, 'bool' => TRUE);
    $options['limit'] = array('default' => 0);
    $options['case'] = array('default' => 'none');
    $options['path_case'] = array('default' => 'none');
    $options['transform_dash'] = array('default' => FALSE, 'bool' => TRUE);
    $options['break_phrase'] = array('default' => FALSE, 'bool' => TRUE);

    if (!empty($this->definition['many to one'])) {
      $options['add_table'] = array('default' => FALSE, 'bool' => TRUE);
      $options['require_value'] = array('default' => FALSE, 'bool' => TRUE);
    }

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['glossary'] = array(
      '#type' => 'checkbox',
      '#title' => t('Glossary mode'),
      '#description' => t('Glossary mode applies a limit to the number of characters used in the filter value, which allows the summary view to act as a glossary.'),
      '#default_value' => $this->options['glossary'],
      '#fieldset' => 'more',
    );

    $form['limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Character limit'),
      '#description' => t('How many characters of the filter value to filter against. If set to 1, all fields starting with the first letter in the filter value would be matched.'),
      '#default_value' => $this->options['limit'],
      '#dependency' => array('edit-options-glossary' => array(TRUE)),
      '#fieldset' => 'more',
    );

    $form['case'] = array(
      '#type' => 'select',
      '#title' => t('Case'),
      '#description' => t('When printing the title and summary, how to transform the case of the filter value.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['case'],
      '#fieldset' => 'more',
    );

    $form['path_case'] = array(
      '#type' => 'select',
      '#title' => t('Case in path'),
      '#description' => t('When printing url paths, how to transform the case of the filter value. Do not use this unless with Postgres as it uses case sensitive comparisons.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['path_case'],
      '#fieldset' => 'more',
    );

    $form['transform_dash'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform spaces to dashes in URL'),
      '#default_value' => $this->options['transform_dash'],
      '#fieldset' => 'more',
    );

    if (!empty($this->definition['many to one'])) {
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
    }

    // allow + for or, , for and
    $form['break_phrase'] = array(
      '#type' => 'checkbox',
      '#title' => t('Allow multiple values'),
      '#description' => t('If selected, users can enter multiple values in the form of 1+2+3 (for OR) or 1,2,3 (for AND).'),
      '#default_value' => !empty($this->options['break_phrase']),
      '#fieldset' => 'more',
    );
  }

  /**
   * Build the summary query based on a string
   */
  function summary_query() {
    if (empty($this->definition['many to one'])) {
      $this->ensure_my_table();
    }
    else {
      $this->table_alias = $this->helper->summary_join();
    }

    if (empty($this->options['glossary'])) {
      // Add the field.
      $this->base_alias = $this->query->add_field($this->table_alias, $this->real_field);
      $this->query->set_count_field($this->table_alias, $this->real_field);
    }
    else {
      // Add the field.
      $formula = $this->get_formula();
      $this->base_alias = $this->query->add_field(NULL, $formula, $this->field . '_truncated');
      $this->query->set_count_field(NULL, $formula, $this->field, $this->field . '_truncated');
    }

    $this->summary_name_field();
    return $this->summary_basics(FALSE);
  }

  /**
   * Get the formula for this argument.
   *
   * $this->ensure_my_table() MUST have been called prior to this.
   */
  function get_formula() {
    return "SUBSTRING($this->table_alias.$this->real_field, 1, " . intval($this->options['limit']) . ")";
  }

  /**
   * Build the query based upon the formula
   */
  function query($group_by = FALSE) {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      views_break_phrase_string($argument, $this);
    }
    else {
      $this->value = array($argument);
      $this->operator = 'or';
    }

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->helper->ensure_my_table();
      $this->helper->add_filter();
      return;
    }

    $this->ensure_my_table();
    $formula = FALSE;
    if (empty($this->options['glossary'])) {
      $field = "$this->table_alias.$this->real_field";
    }
    else {
      $formula = TRUE;
      $field = $this->get_formula();
    }

    if (count($this->value) > 1) {
      $operator = 'IN';
      $argument = $this->value;
    }
    else {
      $operator = '=';
    }

    if ($formula) {
      $placeholder = $this->placeholder();
      if ($operator == 'IN') {
        $field .= " IN($placeholder)";
      }
      else {
        $field .= ' = ' . $placeholder;
      }
      $placeholders = array(
        $placeholder => $argument,
      );
      $this->query->add_where_expression(0, $field, $placeholders);
    }
    else {
      $this->query->add_where(0, $field, $argument, $operator);
    }
  }

  function summary_argument($data) {
    $value = $this->case_transform($data->{$this->base_alias}, $this->options['path_case']);
    if (!empty($this->options['transform_dash'])) {
      $value = strtr($value, ' ', '-');
    }
    return $value;
  }

  function get_sort_name() {
    return t('Alphabetical', array(), array('context' => 'Sort order'));
  }

  function title() {
    $this->argument = $this->case_transform($this->argument, $this->options['case']);
    if (!empty($this->options['transform_dash'])) {
      $this->argument = strtr($this->argument, '-', ' ');
    }

    if (!empty($this->options['break_phrase'])) {
      views_break_phrase_string($this->argument, $this);
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
   */
  function title_query() {
    return drupal_map_assoc($this->value, 'check_plain');
  }

  function summary_name($data) {
    return $this->case_transform(parent::summary_name($data), $this->options['case']);
  }

}
