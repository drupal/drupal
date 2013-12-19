<?php

/**
 * @file
 * Contains Drupal\language\Plugin\views\argument\LanguageArgument.
 */

namespace Drupal\language\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;

/**
 * Defines an argument handler to accept a language.
 *
 * @ingroup views_argument_handlers
 *
 * @PluginID("language")
 */
class LanguageArgument extends ArgumentPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\argument\ArgumentPluginBase::summaryName().
   *
   * Gets the user-friendly version of the language name.
   */
  public function summaryName($data) {
    return $this->language($data->{$this->name_alias});
  }

  /**
   * Overrides \Drupal\views\Plugin\views\argument\ArgumentPluginBase::title().
   *
   * Gets the user friendly version of the language name for display as a
   * title placeholder.
   */
  function title() {
    return $this->language($this->argument);
  }

  /**
   * Returns the language name for a given langcode.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The translated name for the language, or "Unknown language" if the
   *   language was not found.
   */
  function language($langcode) {
    $languages = views_language_list();
    return isset($languages[$langcode]) ? $languages[$langcode] : t('Unknown language');
  }

}
