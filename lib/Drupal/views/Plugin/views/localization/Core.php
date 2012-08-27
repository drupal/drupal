<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\localization\Core.
 */

namespace Drupal\views\Plugin\views\localization;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Localization plugin to pass translatable strings through t().
 *
 * @ingroup views_localization_plugins
 *
 * @Plugin(
 *   id = "core",
 *   title = @Translation("Core"),
 *   help = @Translation("Use Drupal core t() function. Not recommended, as it doesn't support updates to existing strings.")
 * )
 */
class Core extends LocalizationPluginBase {

  /**
   * Translate a string.
   *
   * @param $string
   *   The string to be translated.
   * @param $keys
   *   An array of keys to identify the string. Generally constructed from
   *   view name, display_id, and a property, e.g., 'header'.
   * @param $format
   *   The input format of the string. This is optional.
   */
  function translate_string($string, $keys = array(), $format = '') {
    return t($string);
  }

  /**
   * Save a string for translation.
   *
   * @param $string
   *   The string to be translated.
   * @param $keys
   *   An array of keys to identify the string. Generally constructed from
   *   view name, display_id, and a property, e.g., 'header'.
   * @param $format
   *   The input format of the string. This is optional.
   */
  function save_string($string, $keys = array(), $format = '') {
    $language_interface = language(LANGUAGE_TYPE_INTERFACE);

    // If the current language is 'en', we need to reset the language
    // in order to trigger an update.
    // TODO: add test for number of languages.
    if ($language_interface->langcode == 'en') {
      $changed = TRUE;
      $languages = language_list();
      $cached_language = $language_interface;
      unset($languages['en']);
      if (!empty($languages)) {
        // @todo Rewrite this code.
        //drupal_container()->set(LANGUAGE_TYPE_INTERFACE, current($languages));
      }
    }

    t($string);

    if (isset($cached_language)) {
      // @todo Rewrite this code.
      //drupal_container()->set(LANGUAGE_TYPE_INTERFACE, $cached_language);
    }
    return TRUE;
  }

  /**
   * Delete a string.
   *
   * Deletion is not supported.
   *
   * @param $source
   *   Full data for the string to be translated.
   */
  function delete($source) {
    return FALSE;
  }

  /**
   * Collect strings to be exported to code.
   *
   * String identifiers are not supported so strings are anonymously in an array.
   *
   * @param $source
   *   Full data for the string to be translated.
   */
  function export($source) {
    if (!empty($source['value'])) {
      $this->export_strings[] = $source['value'];
    }
  }

  /**
   * Render any collected exported strings to code.
   *
   * @param $indent
   *   An optional indentation for prettifying nested code.
   */
  function export_render($indent = '  ') {
    $output = '';
    if (!empty($this->export_strings)) {
      $this->export_strings = array_unique($this->export_strings);
      $output = $indent . '$translatables[\'' . $this->view->name . '\'] = array(' . "\n";
      foreach ($this->export_strings as $string) {
        $output .= $indent . "  t('" . str_replace("'", "\'", $string) . "'),\n";
      }
      $output .= $indent . ");\n";
    }
    return $output;
  }

}
