<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\JavaScriptTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Component\Utility\Crypt;

/**
 * Tests the JavaScript system.
 *
 * @group Common
 */
class JavaScriptTest extends DrupalUnitTestBase {

  /**
   * Enable Language and SimpleTest in the test environment.
   *
   * @var array
   */
  public static $modules = array('language', 'simpletest', 'common_test', 'system');

  /**
   * Stores configured value for JavaScript preprocessing.
   */
  protected $preprocess_js = NULL;

  protected function setUp() {
    parent::setUp();

    // Disable preprocessing
    $config = \Drupal::config('system.performance');
    $this->preprocess_js = $config->get('js.preprocess');
    $config->set('js.preprocess', 0);
    $config->save();

    // Reset _drupal_add_js() statics before each test.
    drupal_static_reset('_drupal_add_js');
  }

  protected function tearDown() {
    // Restore configured value for JavaScript preprocessing.
    $config = \Drupal::config('system.performance');
    $config->set('js.preprocess', $this->preprocess_js);
    $config->save();
    parent::tearDown();
  }

  /**
   * Tests that default JavaScript is empty.
   */
  function testDefault() {
    $this->assertEqual(array(), _drupal_add_js(), 'Default JavaScript is empty.');
  }

  /**
   * Tests adding a JavaScript file.
   */
  function testAddFile() {
    $attached['#attached']['js']['core/misc/collapse.js'] = array();
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertTrue(array_key_exists('core/misc/collapse.js', $javascript), 'JavaScript files are correctly added.');
  }

  /**
   * Tests adding settings.
   */
  function testAddSetting() {
    // Add a file in order to test default settings.
    $attached['#attached']['library'][] = 'core/drupalSettings';
    $this->render($attached);
    $javascript = _drupal_add_js();
    $last_settings = reset($javascript['settings']['data']);
    $this->assertTrue(array_key_exists('currentPath', $last_settings['path']), 'The current path JavaScript setting is set correctly.');

    $javascript = _drupal_add_js(array('drupal' => 'rocks', 'dries' => 280342800), 'setting');
    $last_settings = end($javascript['settings']['data']);
    $this->assertEqual(280342800, $last_settings['dries'], 'JavaScript setting is set correctly.');
    $this->assertEqual('rocks', $last_settings['drupal'], 'The other JavaScript setting is set correctly.');
  }

  /**
   * Tests adding an external JavaScript File.
   */
  function testAddExternal() {
    $attached['#attached']['js']['http://example.com/script.js'] = array('type' => 'external');
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertTrue(array_key_exists('http://example.com/script.js', $javascript), 'Added an external JavaScript file.');
  }

  /**
   * Tests adding JavaScript files with additional attributes.
   */
  function testAttributes() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['http://example.com/script.js'] = array(
      'type' => 'external',
      'attributes' => array('defer' => 'defer'),
    );
    $attached['#attached']['js']['core/misc/collapse.js'] = array(
      'attributes' => array('defer' => 'defer'),
    );
    $this->render($attached);
    $javascript = drupal_get_js();

    $expected_1 = '<script src="http://example.com/script.js" defer="defer"></script>';
    $expected_2 = '<script src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '" defer="defer"></script>';

    $this->assertTrue(strpos($javascript, $expected_1) > 0, 'Rendered external JavaScript with correct defer attribute.');
    $this->assertTrue(strpos($javascript, $expected_2) > 0, 'Rendered internal JavaScript with correct defer attribute.');
  }

  /**
   * Tests that attributes are maintained when JS aggregation is enabled.
   */
  function testAggregatedAttributes() {
    // Enable aggregation.
    \Drupal::config('system.performance')->set('js.preprocess', 1)->save();

    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['http://example.com/script.js'] = array(
      'type' => 'external',
      'attributes' => array('defer' => 'defer'),
    );
    $attached['#attached']['js']['core/misc/collapse.js'] = array(
      'attributes' => array('defer' => 'defer'),
    );
    $this->render($attached);
    $javascript = drupal_get_js();

    $expected_1 = '<script src="http://example.com/script.js" defer="defer"></script>';
    $expected_2 = '<script src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '" defer="defer"></script>';

    $this->assertTrue(strpos($javascript, $expected_1) > 0, 'Rendered external JavaScript with correct defer attribute with aggregation enabled.');
    $this->assertTrue(strpos($javascript, $expected_2) > 0, 'Rendered internal JavaScript with correct defer attribute with aggregation enabled.');
  }

  /**
   * Tests drupal_get_js() for JavaScript settings.
   */
  function testHeaderSetting() {
    $attached = array();
    $attached['#attached']['library'][] = 'core/drupalSettings';
    $this->render($attached);

    $javascript = drupal_get_js('header');
    $this->assertTrue(strpos($javascript, 'basePath') > 0, 'Rendered JavaScript header returns basePath setting.');
    $this->assertTrue(strpos($javascript, 'scriptPath') > 0, 'Rendered JavaScript header returns scriptPath setting.');
    $this->assertTrue(strpos($javascript, 'pathPrefix') > 0, 'Rendered JavaScript header returns pathPrefix setting.');
    $this->assertTrue(strpos($javascript, 'currentPath') > 0, 'Rendered JavaScript header returns currentPath setting.');

    // Only the second of these two entries should appear in drupalSettings.
    $attached = array();
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTest' => 'commonTestShouldNotAppear'),
    );
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTest' => 'commonTestShouldAppear'),
    );
    // Only the second of these entries should appear in drupalSettings.
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestJsArrayLiteral' => array('commonTestJsArrayLiteralOldValue')),
    );
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestJsArrayLiteral' => array('commonTestJsArrayLiteralNewValue')),
    );
    // Only the second of these two entries should appear in drupalSettings.
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestJsObjectLiteral' => array('key' => 'commonTestJsObjectLiteralOldValue')),
    );
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestJsObjectLiteral' => array('key' => 'commonTestJsObjectLiteralNewValue')),
    );
    // Real world test case: multiple elements in a render array are adding the
    // same (or nearly the same) JavaScript settings. When merged, they should
    // contain all settings and not duplicate some settings.
    $settings_one = array('moduleName' => array('ui' => array('button A', 'button B'), 'magical flag' => 3.14159265359));
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestRealWorldIdentical' => $settings_one),
    );
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestRealWorldIdentical' => $settings_one),
    );
    $settings_two = array('moduleName' => array('ui' => array('button A', 'button B'), 'magical flag' => 3.14159265359, 'thingiesOnPage' => array('id1' => array())));
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestRealWorldAlmostIdentical' => $settings_two),
    );
    $settings_two = array('moduleName' => array('ui' => array('button C', 'button D'), 'magical flag' => 3.14, 'thingiesOnPage' => array('id2' => array())));
    $attached['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array('commonTestRealWorldAlmostIdentical' => $settings_two),
    );

    $this->render($attached);
    $javascript = drupal_get_js('header');

    // Test whether _drupal_add_js can be used to override a previous setting.
    $this->assertTrue(strpos($javascript, 'commonTestShouldAppear') > 0, 'Rendered JavaScript header returns custom setting.');
    $this->assertTrue(strpos($javascript, 'commonTestShouldNotAppear') === FALSE, '_drupal_add_js() correctly overrides a custom setting.');

    // Test whether _drupal_add_js can be used to add and override a JavaScript
    // array literal (an indexed PHP array) values.
    $array_override = strpos($javascript, 'commonTestJsArrayLiteralNewValue') > 0 && strpos($javascript, 'commonTestJsArrayLiteralOldValue') === FALSE;
    $this->assertTrue($array_override, '_drupal_add_js() correctly overrides settings within an array literal (indexed array).');

    // Test whether _drupal_add_js can be used to add and override a JavaScript
    // object literal (an associate PHP array) values.
    $associative_array_override = strpos($javascript, 'commonTestJsObjectLiteralNewValue') > 0 && strpos($javascript, 'commonTestJsObjectLiteralOldValue') === FALSE;
    $this->assertTrue($associative_array_override, '_drupal_add_js() correctly overrides settings within an object literal (associative array).');

    // Parse the generated drupalSettings <script> back to a PHP representation.
    $startToken = 'drupalSettings = ';
    $endToken = '}';
    $start = strpos($javascript, $startToken) + strlen($startToken);
    $end = strrpos($javascript, $endToken);
    $json  = drupal_substr($javascript, $start, $end - $start + 1);
    $parsed_settings = Json::decode($json);

    // Test whether the two real world cases are handled correctly.
    $settings_two['moduleName']['thingiesOnPage']['id1'] = array();
    $this->assertIdentical($settings_one, $parsed_settings['commonTestRealWorldIdentical'], '_drupal_add_js handled real world test case 1 correctly.');
    $this->assertEqual($settings_two, $parsed_settings['commonTestRealWorldAlmostIdentical'], '_drupal_add_js handled real world test case 2 correctly.');
  }

  /**
   * Tests to see if resetting the JavaScript empties the cache.
   */
  function testReset() {
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/collapse.js'] = array();
    $this->render($attached);
    drupal_static_reset('_drupal_add_js');
    $this->assertEqual(array(), _drupal_add_js(), 'Resetting the JavaScript correctly empties the cache.');
  }

  /**
   * Tests adding inline scripts.
   */
  function testAddInline() {
    $inline = 'jQuery(function () { });';
    $attached['#attached']['library'][] = 'core/jquery';
    $attached['#attached']['js'][] = array(
      'type' => 'inline',
      'data' => $inline,
      'attributes' => array('defer' => 'defer'),
    );
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertTrue(array_key_exists('core/assets/vendor/jquery/jquery.js', $javascript), 'jQuery is added when inline scripts are added.');
    $data = end($javascript);
    $this->assertEqual($inline, $data['data'], 'Inline JavaScript is correctly added to the footer.');
  }

  /**
   * Tests rendering an external JavaScript file.
   */
  function testRenderExternal() {
    $external = 'http://example.com/example.js';
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js'][] = array(
      'type' => 'external',
      'data' => $external,
    );
    $this->render($attached);

    $javascript = drupal_get_js();
    // Local files have a base_path() prefix, external files should not.
    $this->assertTrue(strpos($javascript, 'src="' . $external) > 0, 'Rendering an external JavaScript file.');
  }

  /**
   * Tests drupal_get_js() with a footer scope.
   */
  function testFooterHTML() {
    $inline = 'jQuery(function () { });';
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js'][] = array(
      'type' => 'inline',
      'data' => $inline,
      'scope' => 'footer',
      'attributes' => array('defer' => 'defer'),
    );
    $this->render($attached);

    $javascript = drupal_get_js('footer');
    $this->assertTrue(strpos($javascript, $inline) > 0, 'Rendered JavaScript footer returns the inline code.');
  }

  /**
   * Tests _drupal_add_js() sets preproccess to FALSE when cache is also FALSE.
   */
  function testNoCache() {
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/collapse.js'] = array('cache' => FALSE);
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertFalse($javascript['core/misc/collapse.js']['preprocess'], 'Setting cache to FALSE sets proprocess to FALSE when adding JavaScript.');
  }

  /**
   * Tests adding a JavaScript file with a different group.
   */
  function testDifferentGroup() {
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/collapse.js'] = array('group' => JS_THEME);
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertEqual($javascript['core/misc/collapse.js']['group'], JS_THEME, 'Adding a JavaScript file with a different group caches the given group.');
  }

  /**
   * Tests adding a JavaScript file with a different weight.
   */
  function testDifferentWeight() {
    $attached['#attached']['js']['core/misc/collapse.js'] = array('weight' => 2);
    $this->render($attached);
    $javascript = _drupal_add_js();
    $this->assertEqual($javascript['core/misc/collapse.js']['weight'], 2, 'Adding a JavaScript file with a different weight caches the given weight.');
  }

  /**
   * Tests adding JavaScript within conditional comments.
   *
   * @see \Drupal\Core\Render\Element\HtmlTag::preRenderConditionalComments()
   */
  function testBrowserConditionalComments() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/collapse.js'] = array(
      'browsers' => array('IE' => 'lte IE 8', '!IE' => FALSE),
    );
    $attached['#attached']['js'][] = array(
      'type' => 'inline',
      'data' => 'jQuery(function () { });',
      'browsers' => array('IE' => FALSE),
    );
    $this->render($attached);
    $javascript = drupal_get_js();

    $expected_1 = "<!--[if lte IE 8]>\n" . '<script src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '"></script>' . "\n<![endif]-->";
    $expected_2 = "<!--[if !IE]><!-->\n" . '<script>' . "\n<!--//--><![CDATA[//><!--\n" . 'jQuery(function () { });' . "\n//--><!]]>\n" . '</script>' . "\n<!--<![endif]-->";

    $this->assertTrue(strpos($javascript, $expected_1) > 0, 'Rendered JavaScript within downlevel-hidden conditional comments.');
    $this->assertTrue(strpos($javascript, $expected_2) > 0, 'Rendered JavaScript within downlevel-revealed conditional comments.');
  }

  /**
   * Tests JavaScript versioning.
   */
  function testVersionQueryString() {
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/collapse.js'] = array('version' => 'foo');
    $attached['#attached']['js']['core/misc/ajax.js'] = array('version' => 'bar');
    $this->render($attached);
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'core/misc/collapse.js?v=foo') > 0 && strpos($javascript, 'core/misc/ajax.js?v=bar') > 0 , 'JavaScript version identifiers correctly appended to URLs');
  }

  /**
   * Tests JavaScript grouping and aggregation.
   */
  function testAggregation() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    // To optimize aggregation, items with the 'every_page' option are ordered
    // ahead of ones without. The order of JavaScript execution must be the
    // same regardless of whether aggregation is enabled, so ensure this
    // expected order, first with aggregation off.
    $attached = array();
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/ajax.js'] = array();
    $attached['#attached']['js']['core/misc/collapse.js'] = array('every_page' => TRUE);
    $attached['#attached']['js']['core/misc/autocomplete.js'] = array();
    $attached['#attached']['js']['core/misc/batch.js'] = array('every_page' => TRUE);
    $this->render($attached);
    $javascript = drupal_get_js();
    $expected = implode("\n", array(
      '<script src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '"></script>',
      '<script src="' . file_create_url('core/misc/batch.js') . '?' . $default_query_string . '"></script>',
      '<script src="' . file_create_url('core/misc/ajax.js') . '?' . $default_query_string . '"></script>',
      '<script src="' . file_create_url('core/misc/autocomplete.js') . '?' . $default_query_string . '"></script>',
    ));
    $this->assertTrue(strpos($javascript, $expected) > 0, 'Unaggregated JavaScript is added in the expected group order.');

    // Now ensure that with aggregation on, one file is made for the
    // 'every_page' files, and one file is made for the others.
    drupal_static_reset('_drupal_add_js');
    $config = \Drupal::config('system.performance');
    $config->set('js.preprocess', 1);
    $config->save();
    $attached = array();
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/ajax.js'] = array();
    $attached['#attached']['js']['core/misc/collapse.js'] = array('every_page' => TRUE);
    $attached['#attached']['js']['core/misc/autocomplete.js'] = array();
    $attached['#attached']['js']['core/misc/batch.js'] = array('every_page' => TRUE);
    $this->render($attached);
    $js_items = _drupal_add_js();
    $javascript = drupal_get_js();
    $expected = implode("\n", array(
      '<script src="' . $this->calculateAggregateFilename(array('core/misc/collapse.js' => $js_items['core/misc/collapse.js'], 'core/misc/batch.js' => $js_items['core/misc/batch.js'])) . '"></script>',
      '<script src="' . $this->calculateAggregateFilename(array('core/misc/ajax.js' => $js_items['core/misc/ajax.js'], 'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js'])) . '"></script>',
    ));
    $this->assertTrue(strpos($javascript, $expected) !== FALSE, 'JavaScript is aggregated in the expected groups and order.');
  }

  /**
   * Tests JavaScript aggregation when files are added to a different scope.
   */
  function testAggregationOrder() {
    // Enable JavaScript aggregation.
    \Drupal::config('system.performance')->set('js.preprocess', 1)->save();
    drupal_static_reset('_drupal_add_js');

    // Add two JavaScript files to the current request and build the cache.
    $attached = array();
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['core/misc/ajax.js'] = array();
    $attached['#attached']['js']['core/misc/autocomplete.js'] = array();
    $this->render($attached);

    $js_items = _drupal_add_js();
    $scripts_html = array(
      '#type' => 'scripts',
      '#items' => array(
        'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
        'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
      )
    );
    $this->render($scripts_html);

    // Store the expected key for the first item in the cache.
    $cache = array_keys(\Drupal::state()->get('system.js_cache_files') ?: array());
    $expected_key = $cache[0];

    // Reset variables and add a file in a different scope first.
    \Drupal::state()->delete('system.js_cache_files');
    drupal_static_reset('_drupal_add_js');
    $attached = array();
    $attached['#attached']['library'][] = 'core/drupal';
    $attached['#attached']['js']['some/custom/javascript_file.js'] = array('scope' => 'footer');
    $attached['#attached']['js']['core/misc/ajax.js'] = array();
    $attached['#attached']['js']['core/misc/autocomplete.js'] = array();
    $this->render($attached);

    // Rebuild the cache.
    $js_items = _drupal_add_js();
    $scripts_html = array(
      '#type' => 'scripts',
      '#items' => array(
        'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
        'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
      )
    );
    $this->render($scripts_html);

    // Compare the expected key for the first file to the current one.
    $cache = array_keys(\Drupal::state()->get('system.js_cache_files') ?: array());
    $key = $cache[0];
    $this->assertEqual($key, $expected_key, 'JavaScript aggregation is not affected by ordering in different scopes.');
  }

  /**
   * Tests JavaScript ordering.
   */
  function testRenderOrder() {
    $shared_options = array(
      'type' => 'inline',
      'scope' => 'footer',
    );
    // Add a bunch of JavaScript in strange ordering.
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight 5 #1");})(jQuery);',
      'weight' => 5,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight 0 #1");})(jQuery);',
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight 0 #2");})(jQuery);',
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight -8 #1");})(jQuery);',
      'weight' => -8,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight -8 #2");})(jQuery);',
      'weight' => -8,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight -8 #3");})(jQuery);',
      'weight' => -8,
    );
    $attached['#attached']['js']['http://example.com/example.js?Weight -5 #1'] = array(
      'type' => 'external',
      'scope' => 'footer',
      'weight' => -5,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight -8 #4");})(jQuery);',
      'weight' => -8,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight 5 #2");})(jQuery);',
      'weight' => 5,
    );
    $attached['#attached']['js'][] = $shared_options + array(
      'data' => '(function($){alert("Weight 0 #3");})(jQuery);',
    );
    $this->render($attached);

    // Construct the expected result from the regex.
    $expected = array(
      "-8 #1",
      "-8 #2",
      "-8 #3",
      "-8 #4",
      "-5 #1", // The external script.
      "0 #1",
      "0 #2",
      "0 #3",
      "5 #1",
      "5 #2",
    );

    // Retrieve the rendered JavaScript and test against the regex.
    $js = drupal_get_js('footer');
    $matches = array();
    if (preg_match_all('/Weight\s([-0-9]+\s[#0-9]+)/', $js, $matches)) {
      $result = $matches[1];
    }
    else {
      $result = array();
    }
    $this->assertIdentical($result, $expected, 'JavaScript is added in the expected weight order.');
  }

  /**
   * Tests rendering the JavaScript with a file's weight above jQuery's.
   */
  function testRenderDifferentWeight() {
    // JavaScript files are sorted first by group, then by the 'every_page'
    // flag, then by weight (see drupal_sort_css_js()), so to test the effect of
    // weight, we need the other two options to be the same.
    $attached['#attached']['library'][] = 'core/jquery';
    $attached['#attached']['js']['core/misc/collapse.js'] = array(
      'group' => JS_LIBRARY,
      'every_page' => TRUE,
      'weight' => -21,
    );
    $this->render($attached);
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'core/misc/collapse.js') < strpos($javascript, 'core/assets/vendor/jquery/jquery.js'), 'Rendering a JavaScript file above jQuery.');
  }

  /**
   * Tests altering a JavaScript's weight via hook_js_alter().
   *
   * @see simpletest_js_alter()
   */
  function testAlter() {
    // Add both tableselect.js and simpletest.js, with a larger weight on SimpleTest.
    $attached['#attached']['js']['core/misc/tableselect.js'] = array();
    $attached['#attached']['js'][drupal_get_path('module', 'simpletest') . '/simpletest.js'] = array('weight' => 9999);
    $this->render($attached);

    // Render the JavaScript, testing if simpletest.js was altered to be before
    // tableselect.js. See simpletest_js_alter() to see where this alteration
    // takes place.
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'simpletest.js') < strpos($javascript, 'core/misc/tableselect.js'), 'Altering JavaScript weight through the alter hook.');
  }

  /**
   * Adds a library to the page and tests for both its JavaScript and its CSS.
   */
  function testLibraryRender() {
    $attached = array();
    $attached['#attached']['library'][] = 'core/jquery.farbtastic';
    $this->render($attached);
    $scripts = drupal_get_js();
    $styles = drupal_get_css();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/farbtastic/farbtastic.js'), 'JavaScript of library was added to the page.');
    $this->assertTrue(strpos($styles, 'core/assets/vendor/farbtastic/farbtastic.css'), 'Stylesheet of library was added to the page.');
  }

  /**
   * Adds a JavaScript library to the page and alters it.
   *
   * @see common_test_library_info_alter()
   */
  function testLibraryAlter() {
    // Verify that common_test altered the title of Farbtastic.
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    $library = $library_discovery->getLibraryByName('core', 'jquery.farbtastic');
    $this->assertEqual($library['version'], '0.0', 'Registered libraries were altered.');

    // common_test_library_info_alter() also added a dependency on jQuery Form.
    $attached['#attached']['library'][] = 'core/jquery.farbtastic';
    $this->render($attached);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/jquery-form/jquery.form.js'), 'Altered library dependencies are added to the page.');
  }

  /**
   * Tests that multiple modules can implement the same library.
   *
   * @see common_test.library.yml
   */
  function testLibraryNameConflicts() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    $farbtastic = $library_discovery->getLibraryByName('common_test', 'jquery.farbtastic');
    $this->assertEqual($farbtastic['version'], '0.1', 'Alternative libraries can be added to the page.');
  }

  /**
   * Tests non-existing libraries.
   */
  function testLibraryUnknown() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    $result = $library_discovery->getLibraryByName('unknown', 'unknown');
    $this->assertFalse($result, 'Unknown library returned FALSE.');
    drupal_static_reset('drupal_get_library');

    $attached['#attached']['library'][] = 'unknown/unknown';
    $this->render($attached);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'unknown') === FALSE, 'Unknown library was not added to the page.');
  }

  /**
   * Tests the addition of libraries through the #attached['library'] property.
   */
  function testAttachedLibrary() {
    $element['#attached']['library'][] = 'core/jquery.farbtastic';
    $this->render($element);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/farbtastic/farbtastic.js'), 'The attached_library property adds the additional libraries.');
  }

  /**
   * Tests retrieval of libraries via drupal_get_library().
   */
  function testGetLibrary() {
    /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
    $library_discovery = \Drupal::service('library.discovery');
    // Retrieve all libraries registered by a module.
    $libraries = $library_discovery->getLibrariesByExtension('common_test');
    $this->assertTrue(isset($libraries['jquery.farbtastic']), 'Retrieved all module libraries.');
    // Retrieve all libraries for a module not declaring any libraries.
    // Note: This test installs language module.
    $libraries = $library_discovery->getLibrariesByExtension('dblog');
    $this->assertEqual($libraries, array(), 'Retrieving libraries from a module not declaring any libraries returns an emtpy array.');

    // Retrieve a specific library by module and name.
    $farbtastic = $library_discovery->getLibraryByName('common_test', 'jquery.farbtastic');
    $this->assertEqual($farbtastic['version'], '0.1', 'Retrieved a single library.');
    // Retrieve a non-existing library by module and name.
    $farbtastic = $library_discovery->getLibraryByName('common_test', 'foo');
    $this->assertIdentical($farbtastic, FALSE, 'Retrieving a non-existing library returns FALSE.');
  }

  /**
   * Tests JavaScript files that have querystrings attached get added right.
   */
  function testAddJsFileWithQueryString() {
    $js = drupal_get_path('module', 'node') . '/node.js';
    _drupal_add_js($js);

    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $js . '?' . $query_string), 'Query string was appended correctly to JS.');
  }

  /**
   * Calculates the aggregated file URI of a group of JavaScript assets.
   *
   * @param array $js_assets
   *   A group of JavaScript assets.
   * @return string
   *   A file URI.
   *
   * @see testAggregation()
   * @see testAggregationOrder()
   */
  protected function calculateAggregateFilename($js_assets) {
    $data = '';
    foreach ($js_assets as $js_asset) {
      $data .= file_get_contents($js_asset['data']) . ";\n";
    }
    return file_create_url('public://js/js_' . Crypt::hashBase64($data) . '.js');
  }

}
