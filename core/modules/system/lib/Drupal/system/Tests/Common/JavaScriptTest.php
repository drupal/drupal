<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\JavaScriptTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\Component\Utility\Crypt;

/**
 * Tests the JavaScript system.
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

  public static function getInfo() {
    return array(
      'name' => 'JavaScript',
      'description' => 'Tests the JavaScript system.',
      'group' => 'Common',
    );
  }

  function setUp() {
    parent::setUp();
    // There are dependencies in drupal_get_js() on the theme layer so we need
    // to initialize it.
    drupal_theme_initialize();

    // Disable preprocessing
    $config = \Drupal::config('system.performance');
    $this->preprocess_js = $config->get('js.preprocess');
    $config->set('js.preprocess', 0);
    $config->save();

    // Reset _drupal_add_js() and drupal_add_library() statics before each test.
    drupal_static_reset('_drupal_add_js');
    drupal_static_reset('drupal_add_library');
  }

  function tearDown() {
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
    $javascript = _drupal_add_js('core/misc/collapse.js');
    $this->assertTrue(array_key_exists('core/misc/collapse.js', $javascript), 'JavaScript files are correctly added.');
  }

  /**
   * Tests adding settings.
   */
  function testAddSetting() {
    // Add a file in order to test default settings.
    drupal_add_library('system', 'drupalSettings');
    $javascript = _drupal_add_js();
    $last_settings = reset($javascript['settings']['data']);
    $this->assertTrue(array_key_exists('currentPath', $last_settings), 'The current path JavaScript setting is set correctly.');

    $javascript = _drupal_add_js(array('drupal' => 'rocks', 'dries' => 280342800), 'setting');
    $last_settings = end($javascript['settings']['data']);
    $this->assertEqual(280342800, $last_settings['dries'], 'JavaScript setting is set correctly.');
    $this->assertEqual('rocks', $last_settings['drupal'], 'The other JavaScript setting is set correctly.');
  }

  /**
   * Tests adding an external JavaScript File.
   */
  function testAddExternal() {
    $path = 'http://example.com/script.js';
    $javascript = _drupal_add_js($path, 'external');
    $this->assertTrue(array_key_exists('http://example.com/script.js', $javascript), 'Added an external JavaScript file.');
  }

  /**
   * Tests adding JavaScript files with additional attributes.
   */
  function testAttributes() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    drupal_add_library('system', 'drupal');
    _drupal_add_js('http://example.com/script.js', array('attributes' => array('defer' => 'defer')));
    _drupal_add_js('core/misc/collapse.js', array('attributes' => array('defer' => 'defer')));
    $javascript = drupal_get_js();

    $expected_1 = '<script src="http://example.com/script.js?' . $default_query_string . '" defer="defer"></script>';
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

    drupal_add_library('system', 'drupal');
    _drupal_add_js('http://example.com/script.js', array('attributes' => array('defer' => 'defer')));
    _drupal_add_js('core/misc/collapse.js', array('attributes' => array('defer' => 'defer')));
    $javascript = drupal_get_js();

    $expected_1 = '<script src="http://example.com/script.js?' . $default_query_string . '" defer="defer"></script>';
    $expected_2 = '<script src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '" defer="defer"></script>';

    $this->assertTrue(strpos($javascript, $expected_1) > 0, 'Rendered external JavaScript with correct defer attribute with aggregation enabled.');
    $this->assertTrue(strpos($javascript, $expected_2) > 0, 'Rendered internal JavaScript with correct defer attribute with aggregation enabled.');
  }

  /**
   * Tests drupal_get_js() for JavaScript settings.
   */
  function testHeaderSetting() {
    drupal_add_library('system', 'drupalSettings');

    $javascript = drupal_get_js('header');
    $this->assertTrue(strpos($javascript, 'basePath') > 0, 'Rendered JavaScript header returns basePath setting.');
    $this->assertTrue(strpos($javascript, 'scriptPath') > 0, 'Rendered JavaScript header returns scriptPath setting.');
    $this->assertTrue(strpos($javascript, 'pathPrefix') > 0, 'Rendered JavaScript header returns pathPrefix setting.');
    $this->assertTrue(strpos($javascript, 'currentPath') > 0, 'Rendered JavaScript header returns currentPath setting.');

    // Only the second of these two entries should appear in drupalSettings.
    _drupal_add_js(array('commonTest' => 'commonTestShouldNotAppear'), 'setting');
    _drupal_add_js(array('commonTest' => 'commonTestShouldAppear'), 'setting');
    // Only the second of these entries should appear in drupalSettings.
    _drupal_add_js(array('commonTestJsArrayLiteral' => array('commonTestJsArrayLiteralOldValue')), 'setting');
    _drupal_add_js(array('commonTestJsArrayLiteral' => array('commonTestJsArrayLiteralNewValue')), 'setting');
    // Only the second of these two entries should appear in drupalSettings.
    _drupal_add_js(array('commonTestJsObjectLiteral' => array('key' => 'commonTestJsObjectLiteralOldValue')), 'setting');
    _drupal_add_js(array('commonTestJsObjectLiteral' => array('key' => 'commonTestJsObjectLiteralNewValue')), 'setting');
    // Real world test case: multiple elements in a render array are adding the
    // same (or nearly the same) JavaScript settings. When merged, they should
    // contain all settings and not duplicate some settings.
    $settings_one = array('moduleName' => array('ui' => array('button A', 'button B'), 'magical flag' => 3.14159265359));
    _drupal_add_js(array('commonTestRealWorldIdentical' => $settings_one), 'setting');
    _drupal_add_js(array('commonTestRealWorldIdentical' => $settings_one), 'setting');
    $settings_two = array('moduleName' => array('ui' => array('button A', 'button B'), 'magical flag' => 3.14159265359, 'thingiesOnPage' => array('id1' => array())));
    _drupal_add_js(array('commonTestRealWorldAlmostIdentical' => $settings_two), 'setting');
    $settings_two = array('moduleName' => array('ui' => array('button C', 'button D'), 'magical flag' => 3.14, 'thingiesOnPage' => array('id2' => array())));
    _drupal_add_js(array('commonTestRealWorldAlmostIdentical' => $settings_two), 'setting');

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
    $parsed_settings = drupal_json_decode($json);

    // Test whether the two real world cases are handled correctly.
    $settings_two['moduleName']['thingiesOnPage']['id1'] = array();
    $this->assertIdentical($settings_one, $parsed_settings['commonTestRealWorldIdentical'], '_drupal_add_js handled real world test case 1 correctly.');
    $this->assertEqual($settings_two, $parsed_settings['commonTestRealWorldAlmostIdentical'], '_drupal_add_js handled real world test case 2 correctly.');
  }

  /**
   * Tests to see if resetting the JavaScript empties the cache.
   */
  function testReset() {
    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/collapse.js');
    drupal_static_reset('_drupal_add_js');
    $this->assertEqual(array(), _drupal_add_js(), 'Resetting the JavaScript correctly empties the cache.');
  }

  /**
   * Tests adding inline scripts.
   */
  function testAddInline() {
    drupal_add_library('system', 'jquery');
    $inline = 'jQuery(function () { });';
    $javascript = _drupal_add_js($inline, array('type' => 'inline', 'scope' => 'footer'));
    $this->assertTrue(array_key_exists('core/assets/vendor/jquery/jquery.js', $javascript), 'jQuery is added when inline scripts are added.');
    $data = end($javascript);
    $this->assertEqual($inline, $data['data'], 'Inline JavaScript is correctly added to the footer.');
  }

  /**
   * Tests rendering an external JavaScript file.
   */
  function testRenderExternal() {
    drupal_add_library('system', 'drupal');
    $external = 'http://example.com/example.js';
    _drupal_add_js($external, 'external');
    $javascript = drupal_get_js();
    // Local files have a base_path() prefix, external files should not.
    $this->assertTrue(strpos($javascript, 'src="' . $external) > 0, 'Rendering an external JavaScript file.');
  }

  /**
   * Tests drupal_get_js() with a footer scope.
   */
  function testFooterHTML() {
    drupal_add_library('system', 'drupal');
    $inline = 'jQuery(function () { });';
    _drupal_add_js($inline, array('type' => 'inline', 'scope' => 'footer'));
    $javascript = drupal_get_js('footer');
    $this->assertTrue(strpos($javascript, $inline) > 0, 'Rendered JavaScript footer returns the inline code.');
  }

  /**
   * Tests _drupal_add_js() sets preproccess to FALSE when cache is also FALSE.
   */
  function testNoCache() {
    drupal_add_library('system', 'drupal');
    $javascript = _drupal_add_js('core/misc/collapse.js', array('cache' => FALSE));
    $this->assertFalse($javascript['core/misc/collapse.js']['preprocess'], 'Setting cache to FALSE sets proprocess to FALSE when adding JavaScript.');
  }

  /**
   * Tests adding a JavaScript file with a different group.
   */
  function testDifferentGroup() {
    drupal_add_library('system', 'drupal');
    $javascript = _drupal_add_js('core/misc/collapse.js', array('group' => JS_THEME));
    $this->assertEqual($javascript['core/misc/collapse.js']['group'], JS_THEME, 'Adding a JavaScript file with a different group caches the given group.');
  }

  /**
   * Tests adding a JavaScript file with a different weight.
   */
  function testDifferentWeight() {
    $javascript = _drupal_add_js('core/misc/collapse.js', array('weight' => 2));
    $this->assertEqual($javascript['core/misc/collapse.js']['weight'], 2, 'Adding a JavaScript file with a different weight caches the given weight.');
  }

  /**
   * Tests adding JavaScript within conditional comments.
   *
   * @see drupal_pre_render_conditional_comments()
   */
  function testBrowserConditionalComments() {
    $default_query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';

    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/collapse.js', array('browsers' => array('IE' => 'lte IE 8', '!IE' => FALSE)));
    _drupal_add_js('jQuery(function () { });', array('type' => 'inline', 'browsers' => array('IE' => FALSE)));
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
    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/collapse.js', array('version' => 'foo'));
    _drupal_add_js('core/misc/ajax.js', array('version' => 'bar'));
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
    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/ajax.js');
    _drupal_add_js('core/misc/collapse.js', array('every_page' => TRUE));
    _drupal_add_js('core/misc/autocomplete.js');
    _drupal_add_js('core/misc/batch.js', array('every_page' => TRUE));
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
    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/ajax.js');
    _drupal_add_js('core/misc/collapse.js', array('every_page' => TRUE));
    _drupal_add_js('core/misc/autocomplete.js');
    _drupal_add_js('core/misc/batch.js', array('every_page' => TRUE));
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
    drupal_add_library('system', 'drupal');
    _drupal_add_js('core/misc/ajax.js');
    _drupal_add_js('core/misc/autocomplete.js');

    $js_items = _drupal_add_js();
    $scripts_html = array(
      '#type' => 'scripts',
      '#items' => array(
        'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
        'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
      )
    );
    drupal_render($scripts_html);

    // Store the expected key for the first item in the cache.
    $cache = array_keys(\Drupal::state()->get('system.js_cache_files') ?: array());
    $expected_key = $cache[0];

    // Reset variables and add a file in a different scope first.
    \Drupal::state()->delete('system.js_cache_files');
    drupal_static_reset('_drupal_add_js');
    drupal_add_library('system', 'drupal');
    _drupal_add_js('some/custom/javascript_file.js', array('scope' => 'footer'));
    _drupal_add_js('core/misc/ajax.js');
    _drupal_add_js('core/misc/autocomplete.js');

    // Rebuild the cache.
    $js_items = _drupal_add_js();
    $scripts_html = array(
      '#type' => 'scripts',
      '#items' => array(
        'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
        'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
      )
    );
    drupal_render($scripts_html);

    // Compare the expected key for the first file to the current one.
    $cache = array_keys(\Drupal::state()->get('system.js_cache_files') ?: array());
    $key = $cache[0];
    $this->assertEqual($key, $expected_key, 'JavaScript aggregation is not affected by ordering in different scopes.');
  }

  /**
   * Tests JavaScript ordering.
   */
  function testRenderOrder() {
    // Add a bunch of JavaScript in strange ordering.
    _drupal_add_js('(function($){alert("Weight 5 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => 5));
    _drupal_add_js('(function($){alert("Weight 0 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));
    _drupal_add_js('(function($){alert("Weight 0 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));
    _drupal_add_js('(function($){alert("Weight -8 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    _drupal_add_js('(function($){alert("Weight -8 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    _drupal_add_js('(function($){alert("Weight -8 #3");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    _drupal_add_js('http://example.com/example.js?Weight -5 #1', array('type' => 'external', 'scope' => 'footer', 'weight' => -5));
    _drupal_add_js('(function($){alert("Weight -8 #4");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    _drupal_add_js('(function($){alert("Weight 5 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => 5));
    _drupal_add_js('(function($){alert("Weight 0 #3");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));

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
    drupal_add_library('system', 'jquery');
    _drupal_add_js('core/misc/collapse.js', array('group' => JS_LIBRARY, 'every_page' => TRUE, 'weight' => -21));
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
    _drupal_add_js('core/misc/tableselect.js');
    _drupal_add_js(drupal_get_path('module', 'simpletest') . '/simpletest.js', array('weight' => 9999));

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
    $result = drupal_add_library('system', 'jquery.farbtastic');
    $this->assertTrue($result !== FALSE, 'Library was added without errors.');
    $scripts = drupal_get_js();
    $styles = drupal_get_css();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/farbtastic/farbtastic.js'), 'JavaScript of library was added to the page.');
    $this->assertTrue(strpos($styles, 'core/assets/vendor/farbtastic/farbtastic.css'), 'Stylesheet of library was added to the page.');

    drupal_add_library('common_test', 'shorthand.plugin');
    $path = drupal_get_path('module', 'common_test') . '/js/shorthand.js?v=0.8.3.37';
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, $path), 'JavaScript specified in hook_library_info() using shorthand format (without any options) was added to the page.');
    $this->assertEqual(substr_count($scripts, 'shorthand.js'), 1, 'Shorthand JavaScript file only added once.');
  }

  /**
   * Adds a JavaScript library to the page and alters it.
   *
   * @see common_test_library_info_alter()
   */
  function testLibraryAlter() {
    // Verify that common_test altered the title of Farbtastic.
    $library = drupal_get_library('system', 'jquery.farbtastic');
    $this->assertEqual($library['title'], 'Farbtastic: Altered Library', 'Registered libraries were altered.');

    // common_test_library_info_alter() also added a dependency on jQuery Form.
    drupal_add_library('system', 'jquery.farbtastic');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/jquery-form/jquery.form.js'), 'Altered library dependencies are added to the page.');
  }

  /**
   * Tests that multiple modules can implement the same library.
   *
   * @see common_test_library_info()
   */
  function testLibraryNameConflicts() {
    $farbtastic = drupal_get_library('common_test', 'jquery.farbtastic');
    $this->assertEqual($farbtastic['title'], 'Custom Farbtastic Library', 'Alternative libraries can be added to the page.');
  }

  /**
   * Tests non-existing libraries.
   */
  function testLibraryUnknown() {
    $result = drupal_get_library('unknown', 'unknown');
    $this->assertFalse($result, 'Unknown library returned FALSE.');
    drupal_static_reset('drupal_get_library');

    $result = drupal_add_library('unknown', 'unknown');
    $this->assertFalse($result, 'Unknown library returned FALSE.');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'unknown') === FALSE, 'Unknown library was not added to the page.');
  }

  /**
   * Tests the addition of libraries through the #attached['library'] property.
   */
  function testAttachedLibrary() {
    $element['#attached']['library'][] = array('system', 'jquery.farbtastic');
    drupal_render($element);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/assets/vendor/farbtastic/farbtastic.js'), 'The attached_library property adds the additional libraries.');
  }

  /**
   * Tests retrieval of libraries via drupal_get_library().
   */
  function testGetLibrary() {
    // Retrieve all libraries registered by a module.
    $libraries = drupal_get_library('common_test');
    $this->assertTrue(isset($libraries['jquery.farbtastic']), 'Retrieved all module libraries.');
    // Retrieve all libraries for a module not implementing hook_library_info().
    // Note: This test installs language module.
    $libraries = drupal_get_library('dblog');
    $this->assertEqual($libraries, array(), 'Retrieving libraries from a module not implementing hook_library_info() returns an emtpy array.');

    // Retrieve a specific library by module and name.
    $farbtastic = drupal_get_library('common_test', 'jquery.farbtastic');
    $this->assertEqual($farbtastic['version'], '5.3', 'Retrieved a single library.');
    // Retrieve a non-existing library by module and name.
    $farbtastic = drupal_get_library('common_test', 'foo');
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
