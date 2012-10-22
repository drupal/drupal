<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\argument\Date.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;

/**
 * Abstract argument handler for dates.
 *
 * Adds an option to set a default argument based on the current date.
 *
 * @param $arg_format
 *   The format string to use on the current time when
 *   creating a default date argument.
 *
 * Definitions terms:
 * - many to one: If true, the "many to one" helper will be used.
 * - invalid input: A string to give to the user for obviously invalid input.
 *                  This is deprecated in favor of argument validators.
 *
 * @see Drupal\views\ManyTonOneHelper
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "date"
 * )
 */
class Date extends Formula {

  var $option_name = 'default_argument_date';
  var $arg_format = 'Y-m-d';

  /**
   * Add an option to set the default value to the current date.
   */
  function default_argument_form(&$form, &$form_state) {
    parent::default_argument_form($form, $form_state);
    $form['default_argument_type']['#options'] += array('date' => t('Current date'));
    $form['default_argument_type']['#options'] += array('node_created' => t("Current node's creation time"));
    $form['default_argument_type']['#options'] += array('node_changed' => t("Current node's update time"));  }

  /**
   * Set the empty argument value to the current date,
   * formatted appropriately for this argument.
   */
  function get_default_argument($raw = FALSE) {
    if (!$raw && $this->options['default_argument_type'] == 'date') {
      return date($this->definition['format'], REQUEST_TIME);
    }
    elseif (!$raw && in_array($this->options['default_argument_type'], array('node_created', 'node_changed'))) {
      foreach (range(1, 3) as $i) {
        $node = menu_get_object('node', $i);
        if (!empty($node)) {
          continue;
        }
      }

      if (arg(0) == 'node' && is_numeric(arg(1))) {
        $node = node_load(arg(1));
      }

      if (empty($node)) {
        return parent::get_default_argument();
      }
      elseif ($this->options['default_argument_type'] == 'node_created') {
        return date($this->definition['format'], $node->created);
      }
      elseif ($this->options['default_argument_type'] == 'node_changed') {
        return date($this->definition['format'], $node->changed);
      }
    }

    return parent::get_default_argument($raw);
  }

  function get_sort_name() {
    return t('Date', array(), array('context' => 'Sort order'));
  }

  /**
   * Creates cross-database SQL date extraction.
   *
   * @param string $extract_type
   *   The type of value to extract from the date, like 'MONTH'.
   *
   * @return string
   *   An appropriate SQL string for the DB type and field type.
   */
  public function extractSQL($extract_type) {
    $db_type = Database::getConnection()->databaseType();
    $field = $this->getSQLDateField();

    // Note there is no space after FROM to avoid db_rewrite problems
    // see http://drupal.org/node/79904.
    switch ($extract_type) {
      case 'DATE':
        return $field;
      case 'YEAR':
        return "EXTRACT(YEAR FROM($field))";
      case 'MONTH':
        return "EXTRACT(MONTH FROM($field))";
      case 'DAY':
        return "EXTRACT(DAY FROM($field))";
      case 'HOUR':
        return "EXTRACT(HOUR FROM($field))";
      case 'MINUTE':
        return "EXTRACT(MINUTE FROM($field))";
      case 'SECOND':
        return "EXTRACT(SECOND FROM($field))";
      // ISO week number for date
      case 'WEEK':
        switch ($db_type) {
          case 'mysql':
            // WEEK using arg 3 in mysql should return the same value as postgres
            // EXTRACT.
            return "WEEK($field, 3)";
          case 'pgsql':
            return "EXTRACT(WEEK FROM($field))";
        }
      case 'DOW':
        switch ($db_type) {
          case 'mysql':
            // mysql returns 1 for Sunday through 7 for Saturday php date
            // functions and postgres use 0 for Sunday and 6 for Saturday.
            return "INTEGER(DAYOFWEEK($field) - 1)";
          case 'pgsql':
            return "EXTRACT(DOW FROM($field))";
        }
      case 'DOY':
        switch ($db_type) {
          case 'mysql':
            return "DAYOFYEAR($field)";
          case 'pgsql':
            return "EXTRACT(DOY FROM($field))";
        }
    }
  }

}
