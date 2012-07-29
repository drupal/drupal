<?php

/**
 * @file
 * Definition of Drupal\views_test\Plugin\views\localization\LocalizationTest.
 */

namespace Drupal\views_test\Plugin\views\localization;

use Drupal\views\Plugin\views\localization\LocalizationPluginBase;

/**
 * A stump localisation plugin which has static variables to cache the input.
 *
 * @Plugin(
 *   plugin_id = "test_localization",
 *   title = @Translation("Test."),
 *   help = @Translation("This is a test description."),
 *   no_uid = TRUE
 * )
 */
class LocalizationTest extends LocalizationPluginBase {
  /**
   * Store the strings which was translated.
   */
  var $translated_strings;
  /**
   * Return the string and take sure that the test can find out whether the
   * string got translated.
   */
  function translate_string($string, $keys = array(), $format = '') {
    $this->translated_strings[] = $string;
    return $string . "-translated";
  }

  /**
   * Store the export strings.
   */
  function export($source) {
    if (!empty($source['value'])) {
      $this->export_strings[] = $source['value'];
    }
  }

  /**
   * Return the stored strings for the simpletest.
   */
  function get_export_strings() {
    return $this->export_strings;
  }
}
