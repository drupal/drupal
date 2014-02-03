<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleJavascriptTranslation.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\String;

/**
 * Functional tests for JavaScript parsing for translatable strings.
 */
class LocaleJavascriptTranslation extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale');

  public static function getInfo() {
    return array(
      'name' => 'Javascript translation',
      'description' => 'Tests parsing js files for translatable strings',
      'group' => 'Locale',
    );
  }

  function testFileParsing() {
    $filename = drupal_get_path('module', 'locale') . '/tests/locale_test.js';

    // Parse the file to look for source strings.
    _locale_parse_js_file($filename);

    // Get all of the source strings that were found.
    $strings = $this->container
      ->get('locale.storage')
      ->getStrings(array(
        'type' => 'javascript',
        'name' => $filename,
      ));

    $source_strings = array();
    foreach ($strings as $string) {
      $source_strings[$string->source] = $string->context;
    }

    $etx = LOCALE_PLURAL_DELIMITER;
    // List of all strings that should be in the file.
    $test_strings = array(
      'Standard Call t' => '',
      'Whitespace Call t' => '',

      'Single Quote t' => '',
      "Single Quote \\'Escaped\\' t" => '',
      'Single Quote Concat strings t' => '',

      'Double Quote t' => '',
      "Double Quote \\\"Escaped\\\" t" => '',
      'Double Quote Concat strings t' => '',

      'Context !key Args t' => 'Context string',

      'Context Unquoted t' => 'Context string unquoted',
      'Context Single Quoted t' => 'Context string single quoted',
      'Context Double Quoted t' => 'Context string double quoted',

      "Standard Call plural{$etx}Standard Call @count plural" => '',
      "Whitespace Call plural{$etx}Whitespace Call @count plural" => '',

      "Single Quote plural{$etx}Single Quote @count plural" => '',
      "Single Quote \\'Escaped\\' plural{$etx}Single Quote \\'Escaped\\' @count plural" => '',

      "Double Quote plural{$etx}Double Quote @count plural" => '',
      "Double Quote \\\"Escaped\\\" plural{$etx}Double Quote \\\"Escaped\\\" @count plural" => '',

      "Context !key Args plural{$etx}Context !key Args @count plural" => 'Context string',

      "Context Unquoted plural{$etx}Context Unquoted @count plural" => 'Context string unquoted',
      "Context Single Quoted plural{$etx}Context Single Quoted @count plural" => 'Context string single quoted',
      "Context Double Quoted plural{$etx}Context Double Quoted @count plural" => 'Context string double quoted',
    );

    // Assert that all strings were found properly.
    foreach ($test_strings as $str => $context) {
      $args = array('%source' => $str, '%context' => $context);

      // Make sure that the string was found in the file.
      $this->assertTrue(isset($source_strings[$str]), String::format('Found source string: %source', $args));

      // Make sure that the proper context was matched.
      $message = $context ? String::format('Context for %source is %context', $args) : String::format('Context for %source is blank', $args);
      $this->assertTrue(isset($source_strings[$str]) && $source_strings[$str] === $context, $message);
    }

    $this->assertEqual(count($source_strings), count($test_strings), 'Found correct number of source strings.');
  }
}
