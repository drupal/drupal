<?php

/**
 * @file
 * Definition of Views\language\Plugin\views\filter\Language.
 */

namespace Views\language\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter by language.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "language",
 *   module = "language"
 * )
 */
class Language extends InOperator {

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
