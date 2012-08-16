<?php

/**
 * @file
 * Definition of views_handler_filter_system_type.
 */

namespace Views\system\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter by system type.
 *
 * @Plugin(
 *   id = "system_type",
 *   module = "system"
 * )
 */
class Type extends InOperator {

  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Type');
      // Enable filtering by type.
      $types = array();
      $types = db_query('SELECT DISTINCT(type) FROM {system} ORDER BY type')->fetchAllKeyed(0, 0);
      $this->value_options = $types;
    }
  }

}
