<?php
// $Id: views_plugin_localization_test.inc,v 1.1.4.2 2010/12/01 20:05:06 dereine Exp $

/**
 * A stump localisation plugin which has static variables to cache the input.
 */
class views_plugin_localization_test extends views_plugin_localization {
  /**
   * Store the strings which was translated.
   */
  var $translated_strings;
  /**
   * Return the string and take sure that the test can find out whether the
   * string got translated.
   */
  function translate_string($string, $keys = array()) {
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
