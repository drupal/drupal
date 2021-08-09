<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Component\Gettext\PoItem;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Tests parsing js files for translatable strings.
 *
 * @group locale
 */
class LocaleJavascriptTranslationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['locale', 'locale_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testFileParsing() {

    // This test is for ensuring that the regular expression in
    // _locale_parse_js_file() finds translatable source strings in all valid
    // JavaScript syntax regardless of the coding style used, especially with
    // respect to optional whitespace, line breaks, etc.
    // - We test locale_test.es6.js, because that is the one that contains a
    //   variety of whitespace styles.
    // - We also test the transpiled locale_test.js as an extra double-check
    //   that JavaScript transpilation doesn't change what
    //   _locale_parse_js_file() finds.
    $files[] = __DIR__ . '/../../locale_test.es6.js';
    $files[] = __DIR__ . '/../../locale_test.js';

    foreach ($files as $filename) {
      // Parse the file to look for source strings.
      _locale_parse_js_file($filename);

      // Get all of the source strings that were found.
      $strings = $this->container
        ->get('locale.storage')
        ->getStrings([
          'type' => 'javascript',
          'name' => $filename,
        ]);

      $source_strings = [];
      foreach ($strings as $string) {
        $source_strings[$string->source] = $string->context;
      }

      $etx = PoItem::DELIMITER;
      // List of all strings that should be in the file.
      $test_strings = [
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

        "No count argument plural - singular{$etx}No count argument plural - plural" => '',
      ];

      // Assert that all strings were found properly.
      foreach ($test_strings as $str => $context) {
        $args = ['%source' => $str, '%context' => $context];

        // Make sure that the string was found in the file.
        $this->assertTrue(isset($source_strings[$str]), new FormattableMarkup('Found source string: %source', $args));

        // Make sure that the proper context was matched.
        $this->assertArrayHasKey($str, $source_strings);
        $this->assertSame($context, $source_strings[$str]);
      }

      $this->assertSameSize($test_strings, $source_strings, 'Found correct number of source strings.');
    }
  }

  /**
   * Assert translations JS is added before drupal.js, because it depends on it.
   */
  public function testLocaleTranslationJsDependencies() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
      'translate interface',
    ]);

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'es';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    // The domain prefix.
    $prefix = $langcode;
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');

    // Set path prefix.
    $edit = ["prefix[$langcode]" => $prefix];
    $this->drupalGet('admin/config/regional/language/detection/url');
    $this->submitForm($edit, 'Save configuration');

    // This forces locale.admin.js string sources to be imported, which contains
    // the next translation.
    $this->drupalGet($prefix . '/admin/config/regional/translate');

    // Translate a string in locale.admin.js to our new language.
    $strings = \Drupal::service('locale.storage')
      ->getStrings([
        'source' => 'Show description',
        'type' => 'javascript',
        'name' => 'core/modules/locale/locale.admin.js',
      ]);
    $string = $strings[0];

    $this->submitForm(['string' => 'Show description'], 'Filter');
    $edit = ['strings[' . $string->lid . '][translations][0]' => 'Mostrar descripcion'];
    $this->submitForm($edit, 'Save translations');

    // Calculate the filename of the JS including the translations.
    $js_translation_files = \Drupal::state()->get('locale.translation.javascript');
    $js_filename = $prefix . '_' . $js_translation_files[$prefix] . '.js';

    $content = $this->getSession()->getPage()->getContent();
    $this->assertSession()->responseContains('core/misc/drupal.js');
    $this->assertSession()->responseContains($js_filename);
    // Assert translations JS is included before drupal.js.
    $this->assertLessThan(strpos($content, 'core/misc/drupal.js'), strpos($content, $js_filename));
  }

}
