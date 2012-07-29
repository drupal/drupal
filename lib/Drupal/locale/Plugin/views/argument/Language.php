<?php

/**
 * @file
 * Definition of views_handler_argument_locale_language.
 */

namespace Drupal\locale\Plugin\views\argument;

use Drupal\views\Plugins\views\argument\ArgumentPluginBase;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a language.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "locale_language"
 * )
 */
class Language extends ArgumentPluginBase {
  function construct() {
    parent::construct('language');
  }

  /**
   * Override the behavior of summary_name(). Get the user friendly version
   * of the language.
   */
  function summary_name($data) {
    return $this->locale_language($data->{$this->name_alias});
  }

  /**
   * Override the behavior of title(). Get the user friendly version
   * of the language.
   */
  function title() {
    return $this->locale_language($this->argument);
  }

  function locale_language($langcode) {
    $languages = views_language_list();
    return isset($languages[$langcode]) ? $languages[$langcode] : t('Unknown language');
  }
}
