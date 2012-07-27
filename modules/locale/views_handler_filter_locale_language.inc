<?php

/**
 * @file
 * Definition of views_handler_filter_locale_language.
 */

use Drupal\views\Plugins\views\filter\InOperator;

/**
 * Filter by language.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_locale_language extends InOperator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Language');
      $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
        LANGUAGE_NOT_SPECIFIED => t('No language')
      );
      $languages = array_merge($languages, views_language_list());
      $this->value_options = $languages;
    }
  }
}
