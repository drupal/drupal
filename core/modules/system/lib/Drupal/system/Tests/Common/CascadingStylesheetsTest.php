<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\CascadingStylesheetsTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the Drupal CSS system.
 */
class CascadingStylesheetsTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'system');

  public static function getInfo() {
    return array(
      'name' => 'Cascading stylesheets',
      'description' => 'Tests adding various cascading stylesheets to the page.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp();
    // Reset _drupal_add_css() before each test.
    drupal_static_reset('_drupal_add_css');
  }

  /**
   * Checks that default stylesheets are empty.
   */
  function testDefault() {
    $this->assertEqual(array(), _drupal_add_css(), 'Default CSS is empty.');
  }

  /**
   * Tests adding a file stylesheet.
   */
  function testAddFile() {
    $path = drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css';
    $css = _drupal_add_css($path);
    $this->assertEqual($css['simpletest.module.css']['data'], $path);
  }

  /**
   * Tests adding an external stylesheet.
   */
  function testAddExternal() {
    $path = 'http://example.com/style.css';
    $css = _drupal_add_css($path, 'external');
    $this->assertEqual($css[$path]['type'], 'external', 'Adding an external CSS file caches it properly.');
  }

  /**
   * Makes sure that resetting the CSS empties the cache.
   */
  function testReset() {
    drupal_static_reset('_drupal_add_css');
    $this->assertEqual(array(), _drupal_add_css(), 'Resetting the CSS empties the cache.');
  }

  /**
   * Tests rendering the stylesheets.
   */
  function testRenderFile() {
    $css = drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css';
    _drupal_add_css($css);
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, 'Rendered CSS includes the added stylesheet.');
    // Verify that newlines are properly added inside style tags.
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $css_processed = '<link rel="stylesheet" href="' . check_plain(file_create_url($css)) . "?" . $query_string . '" media="all" />';
    $this->assertEqual(trim($styles), $css_processed, 'Rendered CSS includes newlines inside style tags for JavaScript use.');
  }

  /**
   * Tests rendering an external stylesheet.
   */
  function testRenderExternal() {
    $css = 'http://example.com/style.css';
    _drupal_add_css($css, 'external');
    $styles = drupal_get_css();
    // Stylesheet URL may be the href of a LINK tag or in an @import statement
    // of a STYLE tag.
    $this->assertTrue(strpos($styles, 'href="' . $css) > 0 || strpos($styles, '@import url("' . $css . '")') > 0, 'Rendering an external CSS file.');
  }

  /**
   * Tests rendering inline stylesheets with preprocessing on.
   */
  function testRenderInlinePreprocess() {
    // Turn on CSS aggregation to allow for preprocessing.
    $config = $this->container->get('config.factory')->get('system.performance');
    $config->set('css.preprocess', 1);

    $css = 'body { padding: 0px; }';
    $css_preprocessed = '<style media="all">' . "\n/* <![CDATA[ */\n" . "body{padding:0px;}\n" . "\n/* ]]> */\n" . '</style>';
    _drupal_add_css($css, array('type' => 'inline'));
    $styles = drupal_get_css();
    $this->assertEqual(trim($styles), $css_preprocessed, 'Rendering preprocessed inline CSS adds it to the page.');
  }

  /**
   * Tests rendering inline stylesheets with preprocessing off.
   */
  function testRenderInlineNoPreprocess() {
    $css = 'body { padding: 0px; }';
    _drupal_add_css($css, array('type' => 'inline', 'preprocess' => FALSE));
    $styles = drupal_get_css();
    $this->assertTrue(strpos($styles, $css) > 0, 'Rendering non-preprocessed inline CSS adds it to the page.');
  }

  /**
   * Tests CSS ordering.
   */
  function testRenderOrder() {
    // Load a module CSS file.
    _drupal_add_css(drupal_get_path('module', 'simpletest') . '/css/simpletest.module.css');
    // Load a few system CSS files in a custom, early-loading aggregate group.
    $test_aggregate_group = -100;
    $system_path = drupal_get_path('module', 'system');
    _drupal_add_css($system_path . '/css/system.module.css', array('group' => $test_aggregate_group, 'weight' => -10));
    _drupal_add_css($system_path . '/css/system.theme.css', array('group' => $test_aggregate_group));

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

    _drupal_add_css($system . '/css/system.module.css');
    _drupal_add_css($system . '/tests/css/system.module.css');

    // The dummy stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/tests/css/system.module.css') !== FALSE, 'The overriding CSS file is output.');
    $this->assert(strpos($styles, $system . '/css/system.module.css') === FALSE, 'The overridden CSS file is not output.');

    _drupal_add_css($system . '/tests/css/system.module.css');
    _drupal_add_css($system . '/css/system.module.css');

    // The standard stylesheet should be the only one included.
    $styles = drupal_get_css();
    $this->assert(strpos($styles, $system . '/css/system.module.css') !== FALSE, 'The overriding CSS file is output.');
    $this->assert(strpos($styles, $system . '/tests/css/system.module.css') === FALSE, 'The overridden CSS file is not output.');
  }

  /**
   * Tests that CSS query string remains intact when added to file.
   */
  function testAddCssFileWithQueryString() {
    $css_without_query_string = drupal_get_path('module', 'node') . '/css/node.admin.css';
    $css_with_query_string = '/' . drupal_get_path('module', 'node') . '/node-fake.css?arg1=value1&arg2=value2';
    _drupal_add_css($css_without_query_string);
    _drupal_add_css($css_with_query_string);

    $styles = drupal_get_css();
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $this->assertTrue(strpos($styles, $css_without_query_string . '?' . $query_string), 'Query string was appended correctly to css.');
    $this->assertTrue(strpos($styles, str_replace('&', '&amp;', $css_with_query_string)), 'Query string not escaped on a URI.');
  }
}
