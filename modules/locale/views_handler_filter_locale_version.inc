<?php

/**
 * @file
 * Definition of views_handler_filter_locale_version.
 */

/**
 * Filter by version.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_locale_version extends views_handler_filter_in_operator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Version');
      // Enable filtering by the current installed Drupal version.
      $versions = array('***CURRENT_VERSION***' => t('Current installed version'));
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
