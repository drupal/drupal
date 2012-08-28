<?php

/**
 * @file
 * Definition of Views\locale\Plugin\views\filter\Version.
 */

namespace Views\locale\Plugin\views\filter;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter by version.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "locale_version",
 *   module = "locale"
 * )
 */
class Version extends InOperator {

  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Version');
      // Enable filtering by the current installed Drupal version.
      $versions = array('***CURRENT_VERSION***' => t('Current installed version'));
      // Uses db_query() rather than db_select() because the query is static and
      // does not include any variables.
      $result = db_query('SELECT DISTINCT(version) FROM {locales_source} ORDER BY version');
      foreach ($result as $row) {
        if (!empty($row->version)) {
          $versions[$row->version] = $row->version;
        }
      }
      $this->value_options = $versions;
    }
  }

}
