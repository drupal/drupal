<?php

namespace Drupal\locale\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests parsing js files for translatable strings.
 *
 * @group locale
 */
class LocaleJavascriptTranslationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('locale', 'locale_test');

  public function testFileParsing() {
    $filename = __DIR__ . '/../../tests/locale_test.js';

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
      $this->assertTrue(isset($source_strings[$str]), SafeMarkup::format('Found source string: %source', $args));

      // Make sure that the proper context was matched.
      $message = $context ? SafeMarkup::format('Context for %source is %context', $args) : SafeMarkup::format('Context for %source is blank', $args);
      $this->assertTrue(isset($source_strings[$str]) && $source_strings[$str] === $context, $message);
    }

    $this->assertEqual(count($source_strings), count($test_strings), 'Found correct number of source strings.');
  }

  /**
   * Assert translations JS is added before drupal.js, because it depends on it.
   */
  public function testLocaleTranslationJsDependencies() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'translate interface'));

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'es';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    // The domain prefix.
    $prefix = $langcode;
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Set path prefix.
    $edit = array("prefix[$langcode]" => $prefix);
    $this->drupalPostForm('admin/config/regional/language/detection/url', $edit, t('Save configuration'));

    // This forces locale.admin.js string sources to be imported, which contains
    // the next translation.
    $this->drupalGet($prefix . '/admin/config/regional/translate');

    // Translate a string in locale.admin.js to our new language.
    $strings = \Drupal::service('locale.storage')
      ->getStrings(array(
        'source' => 'Show description',
        'type' => 'javascript',
        'name' => 'core/modules/locale/locale.admin.js',
      ));
    $string = $strings[0];

    $this->drupalPostForm(NULL, ['string' => 'Show description'], t('Filter'));
    $edit = ['strings[' . $string->lid . '][translations][0]' => $this->randomString(16)];
    $this->drupalPostForm(NULL, $edit, t('Save translations'));

    // Calculate the filename of the JS including the translations.
    $js_translation_files = \Drupal::state()->get('locale.translation.javascript');
    $js_filename = $prefix . '_' . $js_translation_files[$prefix] . '.js';

    // Assert translations JS is included before drupal.js.
    $this->assertTrue(strpos($this->content, $js_filename) < strpos($this->content, 'core/misc/drupal.js'), 'Translations are included before Drupal.t.');
  }

}
