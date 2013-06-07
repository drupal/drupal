<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\CascadingStylesheetsTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal CSS system.
 */
class CascadingStylesheetsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'common_test');

  public static function getInfo() {
    return array(
      'name' => 'Cascading stylesheets',
      'description' => 'Tests adding various cascading stylesheets to the page.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp();
    // Reset drupal_add_css() before each test.
    drupal_static_reset('drupal_add_css');
  }

  /**
   * Checks that default stylesheets are empty.
   */
  function testDefault() {
    $this->assertEqual(array(), drupal_add_css(), 'Default CSS is empty.');
  }

  /**
   * Tests adding a file stylesheet.
   */
  function testAddFile() {
    $path = drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css';
    $css = drupal_add_css($path);
    $this->assertEqual($css['simpletest.module.css']['data'], $path);
  }

  /**
   * Tests adding an external stylesheet.
   */
  function testAddExternal() {
    $path = 'http://example.com/style.css';
    $css = drupal_add_css($path, 'external');
    $this->assertEqual($css[$path]['type'], 'external', 'Adding an external CSS file caches it properly.');
  }

  /**
   * Makes sure that resetting the CSS empties the cache.
   */
  function testReset() {
    drupal_static_reset('drupal_add_css');
    $this->assertEqual(array(), drupal_add_css(), 'Resetting the CSS empties the cache.');
  }

  /**
   * Tests rendering the stylesheets.
   */
  function testRenderFile() {
    $css = drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css';
    drupal_add_css($css);
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, 'Rendered CSS includes the added stylesheet.');
    // Verify that newlines are properly added inside style tags.
    $query_string = variable_get('css_js_query_string', '0');
    $css_processed = "<style media=\"all\">\n@import url(\"" . check_plain(file_create_url($css)) . "?" . $query_string ."\");\n</style>";
    $this->assertEqual(trim($styles), $css_processed, 'Rendered CSS includes newlines inside style tags for JavaScript use.');
  }

  /**
   * Tests rendering an external stylesheet.
   */
  function testRenderExternal() {
    $css = 'http://example.com/style.css';
    drupal_add_css($css, 'external');
    $styles = drupal_get_css();
    // Stylesheet URL may be the href of a LINK tag or in an @import statement
    // of a STYLE tag.
    $this->assertTrue(strpos($styles, 'href="' . $css) > 0 || strpos($styles, '@import url("' . $css . '")') > 0, 'Rendering an external CSS file.');
  }

  /**
   * Tests rendering inline stylesheets with preprocessing on.
   */
  function testRenderInlinePreprocess() {
    $css = 'body { padding: 0px; }';
    $css_preprocessed = '<style media="all">' . "\n/* <![CDATA[ */\n" . drupal_load_stylesheet_content($css, TRUE) . "\n/* ]]> */\n" . '</style>';
    drupal_add_css($css, array('type' => 'inline'));
    $styles = drupal_get_css();
    $this->assertEqual(trim($styles), $css_preprocessed, 'Rendering preprocessed inline CSS adds it to the page.');
  }

  /**
   * Tests rendering inline stylesheets with preprocessing off.
   */
  function testRenderInlineNoPreprocess() {
    $css = 'body { padding: 0px; }';
    drupal_add_css($css, array('type' => 'inline', 'preprocess' => FALSE));
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, 'Rendering non-preprocessed inline CSS adds it to the page.');
  }

  /**
   * Tests rendering inline stylesheets through a full page request.
   */
  function testRenderInlineFullPage() {
    module_enable(array('php'));

    $css = 'body { font-size: 254px; }';
    // Inline CSS is minified unless 'preprocess' => FALSE is passed as a
    // drupal_add_css() option.
    $expected = 'body{font-size:254px;}';

    // Create Basic page node type.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));

    // Create a node, using the PHP filter that tests drupal_add_css().
    $php_format_id = 'php_code';
    $settings = array(
      'type' => 'page',
      'body' => array(
        array(
          'value' => t('This tests the inline CSS!') . "<?php drupal_add_css('$css', 'inline'); ?>",
          'format' => $php_format_id,
        ),
      ),
      'promote' => 1,
    );
    $node = $this->drupalCreateNode($settings);

    // Fetch the page.
    $this->drupalGet('node/' . $node->nid);
    $this->assertRaw($expected, 'Inline stylesheets appear in the full page rendering.');
  }

  /**
   * Tests CSS ordering.
   */
  function testRenderOrder() {
    // A module CSS file.
    drupal_add_css(drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css');
    // A few system CSS files, ordered in a strange way.
    $system_path = drupal_get_path('module', 'system');
    drupal_add_css($system_path . '/css/system.module.css', array('group' => CSS_AGGREGATE_SYSTEM, 'weight' => -10));
    drupal_add_css($system_path . '/css/system.theme.css', array('group' => CSS_AGGREGATE_SYSTEM));

    $expected = array(
      $system_path . '/css/system.module.css',
      $system_path . '/css/system.theme.css',
      drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css',
    );

    $styles = drupal_get_css();
    // Stylesheet URL may be the href of a LINK tag or in an @import statement
    // of a STYLE tag.
    if (preg_match_all('/(href="|url\(")' . preg_quote($GLOBALS['base_url'] . '/', '/') . '([^?]+)\?/', $styles, $matches)) {
      $result = $matches[2];
    }
    else {
      $result = array();
    }

    $this->assertIdentical($result, $expected, 'The CSS files are in the expected order.');
  }

  /**
   * Tests CSS override.
   */
  function testRenderOverride() {
    $system = drupal_get_path('module', 'system');

    drupal_add_css($system . '/css/system.module.css');
    drupal_add_css($system . '/tests/css/system.module.css');

    // The dummy stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/tests/css/system.module.css') !== FALSE, 'The overriding CSS file is output.');
    $this->assert(strpos($styles, $system . '/css/system.module.css') === FALSE, 'The overridden CSS file is not output.');

    drupal_add_css($system . '/tests/css/system.module.css');
    drupal_add_css($system . '/css/system.module.css');

    // The standard stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/css/system.module.css') !== FALSE, 'The overriding CSS file is output.');
    $this->assert(strpos($styles, $system . '/tests/css/system.module.css') === FALSE, 'The overridden CSS file is not output.');
  }

  /**
   * Tests Locale module's CSS Alter to include RTL overrides.
   */
  function testAlter() {
    // Switch the language to a right to left language and add system.module.css.
    $language_interface = language(Language::TYPE_INTERFACE);
    $language_interface->direction = Language::DIRECTION_RTL;
    $path = drupal_get_path('module', 'system');
    drupal_add_css($path . '/css/system.module.css');

    // Check to see if system.module-rtl.css was also added.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $path . '/css/system.module-rtl.css') !== FALSE, 'CSS is alterable as right to left overrides are added.');

    // Change the language back to left to right.
    $language_interface->direction = Language::DIRECTION_LTR;
  }

  /**
   * Tests that CSS query string remains intact when added to file.
   */
  function testAddCssFileWithQueryString() {
    $this->drupalGet('common-test/query-string');
    $query_string = variable_get('css_js_query_string', '0');
    $this->assertRaw(drupal_get_path('module', 'node') . '/css/node.admin.css?' . $query_string, 'Query string was appended correctly to css.');
    $this->assertRaw(drupal_get_path('module', 'node') . '/node-fake.css?arg1=value1&amp;arg2=value2', 'Query string not escaped on a URI.');
  }
}
