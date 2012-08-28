<?php

/**
 * @file
 * Definition of Views\system\Plugin\views\filter\Type.
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
      // Uses db_query() rather than db_select() because the query is static and
      // does not include any variables.
      $types = db_query('SELECT DISTINCT(type) FROM {system} ORDER BY type')->fetchAllKeyed(0, 0);
      $this->value_options = $types;
    }
  }

}
