<?php

/**
 * @file
 * Contains Drupal\language\Plugin\views\filter\LanguageFilter.
 */

namespace Drupal\language\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Component\Annotation\Plugin;

/**
 * Provides filtering by language.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "language",
 *   module = "language"
 * )
 */
class LanguageFilter extends InOperator {

  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Language');
      $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
      );
      $languages = array_merge($languages, views_language_list());
      $this->value_options = $languages;
    }
  }

}
