<?php
// $Id: views_handler_argument_string.inc,v 1.5.4.7 2011/01/05 22:40:08 dereine Exp $

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

    $options['glossary'] = array('default' => FALSE);
    $options['limit'] = array('default' => 0);
    $options['case'] = array('default' => 'none');
    $options['path_case'] = array('default' => 'none');
    $options['transform_dash'] = array('default' => FALSE);

    if (!empty($this->definition['many to one'])) {
      $options['add_table'] = array('default' => FALSE);
      $options['require_value'] = array('default' => FALSE);
    }

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['glossary'] = array(
      '#type' => 'checkbox',
      '#title' => t('Glossary mode'),
      '#description' => t('Glossary mode applies a limit to the number of characters used in the argument, which allows the summary view to act as a glossary.'),
      '#default_value' => $this->options['glossary'],
    );

    $form['limit'] = array(
      '#type' => 'textfield',
      '#title' => t('Character limit'),
      '#description' => t('How many characters of the argument to filter against. If set to 1, all fields starting with the letter in the argument would be matched.'),
      '#default_value' => $this->options['limit'],
      '#process' => array('ctools_dependent_process'),
      '#dependency' => array('edit-options-glossary' => array(TRUE)),
    );

    $form['case'] = array(
      '#type' => 'select',
      '#title' => t('Case'),
      '#description' => t('When printing the argument result, how to transform the case.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['case'],
    );

    $form['path_case'] = array(
      '#type' => 'select',
      '#title' => t('Case in path'),
      '#description' => t('When printing url paths, how to transform the case of the argument. Do not use this unless with Postgres as it uses case sensitive comparisons.'),
      '#options' => array(
        'none' => t('No transform'),
        'upper' => t('Upper case'),
        'lower' => t('Lower case'),
        'ucfirst' => t('Capitalize first letter'),
        'ucwords' => t('Capitalize each word'),
      ),
      '#default_value' => $this->options['path_case'],
    );

    $form['transform_dash'] = array(
      '#type' => 'checkbox',
      '#title' => t('Transform spaces to dashes in URL'),
      '#default_value' => $this->options['transform_dash'],
    );

    if (!empty($this->definition['many to one'])) {
      $form['add_table'] = array(
        '#type' => 'checkbox',
        '#title' => t('Allow multiple arguments to work together.'),
        '#description' => t('If selected, multiple instances of this argument can work together, as though multiple terms were supplied to the same argument. This setting is not compatible with the "Reduce duplicates" setting.'),
        '#default_value' => !empty($this->options['add_table']),
      );

      $form['require_value'] = array(
        '#type' => 'checkbox',
        '#title' => t('Do not display items with no value in summary'),
        '#default_value' => !empty($this->options['require_value']),
      );
    }
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
      $this->base_alias = $this->name_alias = $this->query->add_field($this->table_alias, $this->real_field);
      $this->query->set_count_field($this->table_alias, $this->real_field);
    }
    else {
      // Add the field.
      $formula = $this->get_formula();
      $placeholder_length = $this->placeholder_length;
      $params = array(
        'placeholders' => array(
          $placeholder_length => intval($this->options['limit']),
        ),
      );

      $this->base_alias = $this->name_alias = $this->query->add_field(NULL, $formula, $this->field . '_truncated', $params);
      $this->query->set_count_field(NULL, $formula, $this->field, $this->field . '_truncated');
    }

    return $this->summary_basics(FALSE);
  }

  /**
   * Get the formula for this argument.
   *
   * $this->ensure_my_table() MUST have been called prior to this.
   */
  function get_formula() {
    return "SUBSTRING($this->table_alias.$this->real_field, 1, $this->placeholder_length)";
  }

  /**
   * Build the query based upon the formula
   */
  function query() {
    $argument = $this->argument;
    if (!empty($this->options['transform_dash'])) {
      $argument = strtr($argument, '-', ' ');
    }
    $this->placeholder_length = $this->placeholder();

    if (!empty($this->definition['many to one'])) {
      if (!empty($this->options['glossary'])) {
        $this->helper->formula = TRUE;
      }
      $this->value = array($argument);
      $this->helper->ensure_my_table();

      $this->helper->placeholders = array($this->placeholder_length => intval($this->options['limit']));
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

    if ($formula) {
      $placeholder = $this->placeholder();
      $placeholder_length = $this->placeholder_length;
      $field .= ' = ' . $placeholder;
      $placeholders = array(
        $placeholder_length => intval($this->options['limit']),
        $placeholder => $argument,
      );
      $this->query->add_where_expression(0, $field, $placeholders);
    }
    else {
      $this->query->add_where(0, $field, $argument);
    }
  }

  function summary_argument($data) {
    $value = $this->case_transform($data->{$this->base_alias}, 'path_case');
    if (!empty($this->options['transform_dash'])) {
      $value = strtr($value, ' ', '-');
    }
    return $value;
  }

  function case_transform($string, $option) {
    global $multibyte;

    switch ($this->options[$option]) {
      default:
        return $string;
      case 'upper':
        return drupal_strtoupper($string);
      case 'lower':
        return drupal_strtolower($string);
      case 'ucfirst':
        return drupal_strtoupper(drupal_substr($string, 0, 1)) . drupal_substr($string, 1);
      case 'ucwords':
        if ($multibyte == UNICODE_MULTIBYTE) {
          return mb_convert_case($string, MB_CASE_TITLE);
        } else {
          return ucwords($string);
        }
    }
  }

  function title() {
    $title = $this->case_transform($this->argument, 'case');
    if (!empty($this->options['transform_dash'])) {
      $title = strtr($title, '-', ' ');
    }

    return check_plain($title);
  }

  function summary_name($data) {
    return $this->case_transform(parent::summary_name($data), 'case');
  }

}

