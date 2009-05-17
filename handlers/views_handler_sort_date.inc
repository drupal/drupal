<?php

/**
 * @file
 * Definition of views_handler_sort_date.
 */

/**
 * Basic sort handler for dates.
 *
 * This handler enables granularity, which is the ability to make dates
 * equivalent based upon nearness.
 *
 * @ingroup views_sort_handlers
 */
class views_handler_sort_date extends views_handler_sort {
  function option_definition() {
    $options = parent::option_definition();

    $options['granularity'] = array('default' => 'second');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);

    $form['granularity'] = array(
      '#type' => 'radios',
      '#title' => t('Granularity'),
      '#options' => array(
        'second' => t('Second'),
        'minute' => t('Minute'),
        'hour'   => t('Hour'),
        'day'    => t('Day'),
        'month'  => t('Month'),
        'year'   => t('Year'),
      ),
      '#description' => t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['granularity'],
    );
  }

  /**
   * Called to add the sort to a query.
   */
  function query() {
    $this->ensure_my_table();
    switch ($this->options['granularity']) {
      case 'second':
      default:
        $this->query->add_orderby($this->table_alias, $this->real_field, $this->options['order']);
        return;
      case 'minute':
        $formula = views_date_sql_format('YmdHi', "$this->table_alias.$this->real_field");
        break;
      case 'hour':
        $formula = views_date_sql_format('YmdH', "$this->table_alias.$this->real_field");
        break;
      case 'day':
        $formula = views_date_sql_format('Ymd', "$this->table_alias.$this->real_field");
        break;
      case 'month':
        $formula = views_date_sql_format('Ym', "$this->table_alias.$this->real_field");
        break;
      case 'year':
        $formula = views_date_sql_format('Y', "$this->table_alias.$this->real_field");
        break;
    }

    // Add the field.
    $this->query->add_orderby(NULL, $formula, $this->options['order'], $this->table_alias . '_' . $this->field . '_' . $this->options['granularity']);
  }
}
