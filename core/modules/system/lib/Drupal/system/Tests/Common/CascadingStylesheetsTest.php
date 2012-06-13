<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\CascadingStylesheetsTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal CSS system.
 */
class CascadingStylesheetsTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Cascading stylesheets',
      'description' => 'Tests adding various cascading stylesheets to the page.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp(array('language', 'common_test'));
    // Reset drupal_add_css() before each test.
    drupal_static_reset('drupal_add_css');
  }

  /**
   * Check default stylesheets as empty.
   */
  function testDefault() {
    $this->assertEqual(array(), drupal_add_css(), t('Default CSS is empty.'));
  }

  /**
   * Test that stylesheets in module .info files are loaded.
   */
  function testModuleInfo() {
    $this->drupalGet('');

    // Verify common_test.css in a STYLE media="all" tag.
    $elements = $this->xpath('//style[@media=:media and contains(text(), :filename)]', array(
      ':media' => 'all',
      ':filename' => 'tests/modules/common_test/common_test.css',
    ));
    $this->assertTrue(count($elements), "Stylesheet with media 'all' in module .info file found.");

    // Verify common_test.print.css in a STYLE media="print" tag.
    $elements = $this->xpath('//style[@media=:media and contains(text(), :filename)]', array(
      ':media' => 'print',
      ':filename' => 'tests/modules/common_test/common_test.print.css',
    ));
    $this->assertTrue(count($elements), "Stylesheet with media 'print' in module .info file found.");
  }

  /**
   * Tests adding a file stylesheet.
   */
  function testAddFile() {
    $path = drupal_get_path('module', 'simpletest') . '/simpletest.css';
    $css = drupal_add_css($path);
    $this->assertEqual($css[$path]['data'], $path, t('Adding a CSS file caches it properly.'));
  }

  /**
   * Tests adding an external stylesheet.
   */
  function testAddExternal() {
    $path = 'http://example.com/style.css';
    $css = drupal_add_css($path, 'external');
    $this->assertEqual($css[$path]['type'], 'external', t('Adding an external CSS file caches it properly.'));
  }

  /**
   * Makes sure that reseting the CSS empties the cache.
   */
  function testReset() {
    drupal_static_reset('drupal_add_css');
    $this->assertEqual(array(), drupal_add_css(), t('Resetting the CSS empties the cache.'));
  }

  /**
   * Tests rendering the stylesheets.
   */
  function testRenderFile() {
    $css = drupal_get_path('module', 'simpletest') . '/simpletest.css';
    drupal_add_css($css);
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, t('Rendered CSS includes the added stylesheet.'));
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
    $this->assertTrue(strpos($styles, 'href="' . $css) > 0 || strpos($styles, '@import url("' . $css . '")') > 0, t('Rendering an external CSS file.'));
  }

  /**
   * Tests rendering inline stylesheets with preprocessing on.
   */
  function testRenderInlinePreprocess() {
    $css = 'body { padding: 0px; }';
    $css_preprocessed = '<style media="all">' . "\n<!--/*--><![CDATA[/*><!--*/\n" . drupal_load_stylesheet_content($css, TRUE) . "\n/*]]>*/-->\n" . '</style>';
    drupal_add_css($css, array('type' => 'inline'));
    $styles = drupal_get_css();
    $this->assertEqual(trim($styles), $css_preprocessed, t('Rendering preprocessed inline CSS adds it to the page.'));
  }

  /**
   * Tests rendering inline stylesheets with preprocessing off.
   */
  function testRenderInlineNoPreprocess() {
    $css = 'body { padding: 0px; }';
    drupal_add_css($css, array('type' => 'inline', 'preprocess' => FALSE));
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, t('Rendering non-preprocessed inline CSS adds it to the page.'));
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
        LANGUAGE_NOT_SPECIFIED => array(
          array(
            'value' => t('This tests the inline CSS!') . "<?php drupal_add_css('$css', 'inline'); ?>",
            'format' => $php_format_id,
          ),
        ),
      ),
      'promote' => 1,
    );
    $node = $this->drupalCreateNode($settings);

    // Fetch the page.
    $this->drupalGet('node/' . $node->nid);
    $this->assertRaw($expected, t('Inline stylesheets appear in the full page rendering.'));
  }

  /**
   * Test CSS ordering.
   */
  function testRenderOrder() {
    // A module CSS file.
    drupal_add_css(drupal_get_path('module', 'simpletest') . '/simpletest.css');
    // A few system CSS files, ordered in a strange way.
    $system_path = drupal_get_path('module', 'system');
    drupal_add_css($system_path . '/system.base.css', array('group' => CSS_SYSTEM, 'weight' => -10));
    drupal_add_css($system_path . '/system.theme.css', array('group' => CSS_SYSTEM));

    $expected = array(
      $system_path . '/system.base.css',
      $system_path . '/system.theme.css',
      drupal_get_path('module', 'simpletest') . '/simpletest.css',
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

    $this->assertIdentical($result, $expected, t('The CSS files are in the expected order.'));
  }

  /**
   * Test CSS override.
   */
  function testRenderOverride() {
    $system = drupal_get_path('module', 'system');

    drupal_add_css($system . '/system.base.css');
    drupal_add_css($system . '/tests/system.base.css');

    // The dummy stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/tests/system.base.css') !== FALSE, t('The overriding CSS file is output.'));
    $this->assert(strpos($styles, $system . '/system.base.css') === FALSE, t('The overridden CSS file is not output.'));

    drupal_add_css($system . '/tests/system.base.css');
    drupal_add_css($system . '/system.base.css');

    // The standard stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/system.base.css') !== FALSE, t('The overriding CSS file is output.'));
    $this->assert(strpos($styles, $system . '/tests/system.base.css') === FALSE, t('The overridden CSS file is not output.'));
  }

  /**
   * Tests Locale module's CSS Alter to include RTL overrides.
   */
  function testAlter() {
    // Switch the language to a right to left language and add system.base.css.
    $language_interface = drupal_container()->get(LANGUAGE_TYPE_INTERFACE);
    $language_interface->direction = LANGUAGE_RTL;
    $path = drupal_get_path('module', 'system');
    drupal_add_css($path . '/system.base.css');

    // Check to see if system.base-rtl.css was also added.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $path . '/system.base-rtl.css') !== FALSE, t('CSS is alterable as right to left overrides are added.'));

    // Change the language back to left to right.
    $language_interface->direction = LANGUAGE_LTR;
  }

  /**
   * Tests that the query string remains intact when adding CSS files that have
   * query string parameters.
   */
  function testAddCssFileWithQueryString() {
    $this->drupalGet('common-test/query-string');
    $query_string = variable_get('css_js_query_string', '0');
    $this->assertRaw(drupal_get_path('module', 'node') . '/node.admin.css?' . $query_string, t('Query string was appended correctly to css.'));
    $this->assertRaw(drupal_get_path('module', 'node') . '/node-fake.css?arg1=value1&amp;arg2=value2', t('Query string not escaped on a URI.'));
  }
}
