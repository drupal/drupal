<?php

/**
 * @file
 * Definition of Views\language\Plugin\views\argument\Language.
 */

namespace Views\language\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a language.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "language",
 *   module = "language"
 * )
 */
class Language extends ArgumentPluginBase {

  /**
   * Override the behavior of summary_name(). Get the user friendly version
   * of the language.
   */
  function summary_name($data) {
    return $this->language($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version
   * of the language.
   */
  function title() {
    return $this->language($this->argument);
  }

  function language($langcode) {
    $languages = views_language_list();
    return isset($languages[$langcode]) ? $languages[$langcode] : t('Unknown language');
  }

}
