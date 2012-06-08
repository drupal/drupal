<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\JavaScriptTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the JavaScript system.
 */
class JavaScriptTest extends WebTestBase {
  /**
   * Store configured value for JavaScript preprocessing.
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
    // Enable Language and SimpleTest in the test environment.
    parent::setUp('language', 'simpletest', 'common_test');

    // Disable preprocessing
    $config = config('system.performance');
    $this->preprocess_js = $config->get('preprocess_js');
    $config->set('preprocess_js', 0);
    $config->save();

    // Reset drupal_add_js() and drupal_add_library() statics before each test.
    drupal_static_reset('drupal_add_js');
    drupal_static_reset('drupal_add_library');
  }

  function tearDown() {
    // Restore configured value for JavaScript preprocessing.
    $config = config('system.performance');
    $config->set('preprocess_js', $this->preprocess_js);
    $config->save();
    parent::tearDown();
  }

  /**
   * Test default JavaScript is empty.
   */
  function testDefault() {
    $this->assertEqual(array(), drupal_add_js(), t('Default JavaScript is empty.'));
  }

  /**
   * Test adding a JavaScript file.
   */
  function testAddFile() {
    $javascript = drupal_add_js('core/misc/collapse.js');
    $this->assertTrue(array_key_exists('core/misc/jquery.js', $javascript), t('jQuery is added when a file is added.'));
    $this->assertTrue(array_key_exists('core/misc/drupal.js', $javascript), t('Drupal.js is added when file is added.'));
    $this->assertTrue(array_key_exists('core/misc/html5.js', $javascript), t('html5.js is added when file is added.'));
    $this->assertTrue(array_key_exists('core/misc/collapse.js', $javascript), t('JavaScript files are correctly added.'));
    $this->assertEqual(base_path(), $javascript['settings']['data'][0]['basePath'], t('Base path JavaScript setting is correctly set.'));
    $this->assertIdentical($GLOBALS['script_path'], $javascript['settings']['data'][1]['scriptPath'], t('Script path JavaScript setting is correctly set.'));
    $this->assertIdentical('', $javascript['settings']['data'][2]['pathPrefix'], t('Path prefix JavaScript setting is correctly set.'));
  }

  /**
   * Test adding settings.
   */
  function testAddSetting() {
    $javascript = drupal_add_js(array('drupal' => 'rocks', 'dries' => 280342800), 'setting');
    $this->assertEqual(280342800, $javascript['settings']['data'][3]['dries'], t('JavaScript setting is set correctly.'));
    $this->assertEqual('rocks', $javascript['settings']['data'][3]['drupal'], t('The other JavaScript setting is set correctly.'));
  }

  /**
   * Tests adding an external JavaScript File.
   */
  function testAddExternal() {
    $path = 'http://example.com/script.js';
    $javascript = drupal_add_js($path, 'external');
    $this->assertTrue(array_key_exists('http://example.com/script.js', $javascript), t('Added an external JavaScript file.'));
  }

  /**
   * Tests adding external JavaScript Files with the async attribute.
   */
  function testAsyncAttribute() {
    $default_query_string = variable_get('css_js_query_string', '0');

    drupal_add_js('http://example.com/script.js', array('async' => TRUE));
    drupal_add_js('core/misc/collapse.js', array('async' => TRUE));
    $javascript = drupal_get_js();

    $expected_1 = '<script type="text/javascript" src="http://example.com/script.js?' . $default_query_string . '" async="async"></script>';
    $expected_2 = '<script type="text/javascript" src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '" async="async"></script>';

    $this->assertTrue(strpos($javascript, $expected_1) > 0, t('Rendered external JavaScript with correct async attribute.'));
    $this->assertTrue(strpos($javascript, $expected_2) > 0, t('Rendered internal JavaScript with correct async attribute.'));
  }

  /**
   * Tests adding external JavaScript Files with the defer attribute.
   */
  function testDeferAttribute() {
    $default_query_string = variable_get('css_js_query_string', '0');

    drupal_add_js('http://example.com/script.js', array('defer' => TRUE));
    drupal_add_js('core/misc/collapse.js', array('defer' => TRUE));
    $javascript = drupal_get_js();

    $expected_1 = '<script type="text/javascript" src="http://example.com/script.js?' . $default_query_string . '" defer="defer"></script>';
    $expected_2 = '<script type="text/javascript" src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '" defer="defer"></script>';

    $this->assertTrue(strpos($javascript, $expected_1) > 0, t('Rendered external JavaScript with correct defer attribute.'));
    $this->assertTrue(strpos($javascript, $expected_2) > 0, t('Rendered internal JavaScript with correct defer attribute.'));
  }

  /**
   * Test drupal_get_js() for JavaScript settings.
   */
  function testHeaderSetting() {
    // Only the second of these two entries should appear in Drupal.settings.
    drupal_add_js(array('commonTest' => 'commonTestShouldNotAppear'), 'setting');
    drupal_add_js(array('commonTest' => 'commonTestShouldAppear'), 'setting');
    // All three of these entries should appear in Drupal.settings.
    drupal_add_js(array('commonTestArray' => array('commonTestValue0')), 'setting');
    drupal_add_js(array('commonTestArray' => array('commonTestValue1')), 'setting');
    drupal_add_js(array('commonTestArray' => array('commonTestValue2')), 'setting');
    // Only the second of these two entries should appear in Drupal.settings.
    drupal_add_js(array('commonTestArray' => array('key' => 'commonTestOldValue')), 'setting');
    drupal_add_js(array('commonTestArray' => array('key' => 'commonTestNewValue')), 'setting');

    $javascript = drupal_get_js('header');
    $this->assertTrue(strpos($javascript, 'basePath') > 0, t('Rendered JavaScript header returns basePath setting.'));
    $this->assertTrue(strpos($javascript, 'scriptPath') > 0, t('Rendered JavaScript header returns scriptPath setting.'));
    $this->assertTrue(strpos($javascript, 'pathPrefix') > 0, t('Rendered JavaScript header returns pathPrefix setting.'));
    $this->assertTrue(strpos($javascript, 'core/misc/jquery.js') > 0, t('Rendered JavaScript header includes jQuery.'));

    // Test whether drupal_add_js can be used to override a previous setting.
    $this->assertTrue(strpos($javascript, 'commonTestShouldAppear') > 0, t('Rendered JavaScript header returns custom setting.'));
    $this->assertTrue(strpos($javascript, 'commonTestShouldNotAppear') === FALSE, t('drupal_add_js() correctly overrides a custom setting.'));

    // Test whether drupal_add_js can be used to add numerically indexed values
    // to an array.
    $array_values_appear = strpos($javascript, 'commonTestValue0') > 0 && strpos($javascript, 'commonTestValue1') > 0 && strpos($javascript, 'commonTestValue2') > 0;
    $this->assertTrue($array_values_appear, t('drupal_add_js() correctly adds settings to the end of an indexed array.'));

    // Test whether drupal_add_js can be used to override the entry for an
    // existing key in an associative array.
    $associative_array_override = strpos($javascript, 'commonTestNewValue') > 0 && strpos($javascript, 'commonTestOldValue') === FALSE;
    $this->assertTrue($associative_array_override, t('drupal_add_js() correctly overrides settings within an associative array.'));
  }

  /**
   * Test to see if resetting the JavaScript empties the cache.
   */
  function testReset() {
    drupal_add_js('core/misc/collapse.js');
    drupal_static_reset('drupal_add_js');
    $this->assertEqual(array(), drupal_add_js(), t('Resetting the JavaScript correctly empties the cache.'));
  }

  /**
   * Test adding inline scripts.
   */
  function testAddInline() {
    $inline = 'jQuery(function () { });';
    $javascript = drupal_add_js($inline, array('type' => 'inline', 'scope' => 'footer'));
    $this->assertTrue(array_key_exists('core/misc/jquery.js', $javascript), t('jQuery is added when inline scripts are added.'));
    $data = end($javascript);
    $this->assertEqual($inline, $data['data'], t('Inline JavaScript is correctly added to the footer.'));
  }

  /**
   * Test rendering an external JavaScript file.
   */
  function testRenderExternal() {
    $external = 'http://example.com/example.js';
    drupal_add_js($external, 'external');
    $javascript = drupal_get_js();
    // Local files have a base_path() prefix, external files should not.
    $this->assertTrue(strpos($javascript, 'src="' . $external) > 0, t('Rendering an external JavaScript file.'));
  }

  /**
   * Test drupal_get_js() with a footer scope.
   */
  function testFooterHTML() {
    $inline = 'jQuery(function () { });';
    drupal_add_js($inline, array('type' => 'inline', 'scope' => 'footer'));
    $javascript = drupal_get_js('footer');
    $this->assertTrue(strpos($javascript, $inline) > 0, t('Rendered JavaScript footer returns the inline code.'));
  }

  /**
   * Test drupal_add_js() sets preproccess to false when cache is set to false.
   */
  function testNoCache() {
    $javascript = drupal_add_js('core/misc/collapse.js', array('cache' => FALSE));
    $this->assertFalse($javascript['core/misc/collapse.js']['preprocess'], t('Setting cache to FALSE sets proprocess to FALSE when adding JavaScript.'));
  }

  /**
   * Test adding a JavaScript file with a different group.
   */
  function testDifferentGroup() {
    $javascript = drupal_add_js('core/misc/collapse.js', array('group' => JS_THEME));
    $this->assertEqual($javascript['core/misc/collapse.js']['group'], JS_THEME, t('Adding a JavaScript file with a different group caches the given group.'));
  }

  /**
   * Test adding a JavaScript file with a different weight.
   */
  function testDifferentWeight() {
    $javascript = drupal_add_js('core/misc/collapse.js', array('weight' => 2));
    $this->assertEqual($javascript['core/misc/collapse.js']['weight'], 2, t('Adding a JavaScript file with a different weight caches the given weight.'));
  }

  /**
   * Test adding JavaScript within conditional comments.
   *
   * @see drupal_pre_render_conditional_comments()
   */
  function testBrowserConditionalComments() {
    $default_query_string = variable_get('css_js_query_string', '0');

    drupal_add_js('core/misc/collapse.js', array('browsers' => array('IE' => 'lte IE 8', '!IE' => FALSE)));
    drupal_add_js('jQuery(function () { });', array('type' => 'inline', 'browsers' => array('IE' => FALSE)));
    $javascript = drupal_get_js();

    $expected_1 = "<!--[if lte IE 8]>\n" . '<script type="text/javascript" src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '"></script>' . "\n<![endif]-->";
    $expected_2 = "<!--[if !IE]><!-->\n" . '<script type="text/javascript">' . "\n<!--//--><![CDATA[//><!--\n" . 'jQuery(function () { });' . "\n//--><!]]>\n" . '</script>' . "\n<!--<![endif]-->";

    $this->assertTrue(strpos($javascript, $expected_1) > 0, t('Rendered JavaScript within downlevel-hidden conditional comments.'));
    $this->assertTrue(strpos($javascript, $expected_2) > 0, t('Rendered JavaScript within downlevel-revealed conditional comments.'));
  }

  /**
   * Test JavaScript versioning.
   */
  function testVersionQueryString() {
    drupal_add_js('core/misc/collapse.js', array('version' => 'foo'));
    drupal_add_js('core/misc/ajax.js', array('version' => 'bar'));
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'core/misc/collapse.js?v=foo') > 0 && strpos($javascript, 'core/misc/ajax.js?v=bar') > 0 , t('JavaScript version identifiers correctly appended to URLs'));
  }

  /**
   * Test JavaScript grouping and aggregation.
   */
  function testAggregation() {
    $default_query_string = variable_get('css_js_query_string', '0');

    // To optimize aggregation, items with the 'every_page' option are ordered
    // ahead of ones without. The order of JavaScript execution must be the
    // same regardless of whether aggregation is enabled, so ensure this
    // expected order, first with aggregation off.
    drupal_add_js('core/misc/ajax.js');
    drupal_add_js('core/misc/collapse.js', array('every_page' => TRUE));
    drupal_add_js('core/misc/autocomplete.js');
    drupal_add_js('core/misc/batch.js', array('every_page' => TRUE));
    $javascript = drupal_get_js();
    $expected = implode("\n", array(
      '<script type="text/javascript" src="' . file_create_url('core/misc/collapse.js') . '?' . $default_query_string . '"></script>',
      '<script type="text/javascript" src="' . file_create_url('core/misc/batch.js') . '?' . $default_query_string . '"></script>',
      '<script type="text/javascript" src="' . file_create_url('core/misc/ajax.js') . '?' . $default_query_string . '"></script>',
      '<script type="text/javascript" src="' . file_create_url('core/misc/autocomplete.js') . '?' . $default_query_string . '"></script>',
    ));
    $this->assertTrue(strpos($javascript, $expected) > 0, t('Unaggregated JavaScript is added in the expected group order.'));

    // Now ensure that with aggregation on, one file is made for the
    // 'every_page' files, and one file is made for the others.
    drupal_static_reset('drupal_add_js');
    $config = config('system.performance');
    $config->set('preprocess_js', 1);
    $config->save();
    drupal_add_js('core/misc/ajax.js');
    drupal_add_js('core/misc/collapse.js', array('every_page' => TRUE));
    drupal_add_js('core/misc/autocomplete.js');
    drupal_add_js('core/misc/batch.js', array('every_page' => TRUE));
    $js_items = drupal_add_js();
    $javascript = drupal_get_js();
    $expected = implode("\n", array(
      '<script type="text/javascript" src="' . file_create_url(drupal_build_js_cache(array('core/misc/collapse.js' => $js_items['core/misc/collapse.js'], 'core/misc/batch.js' => $js_items['core/misc/batch.js']))) . '"></script>',
      '<script type="text/javascript" src="' . file_create_url(drupal_build_js_cache(array('core/misc/ajax.js' => $js_items['core/misc/ajax.js'], 'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']))) . '"></script>',
    ));
    $this->assertTrue(strpos($javascript, $expected) > 0, t('JavaScript is aggregated in the expected groups and order.'));
  }

  /**
   * Tests JavaScript aggregation when files are added to a different scope.
   */
  function testAggregationOrder() {
    // Enable JavaScript aggregation.
    config('system.performance')->set('preprocess_js', 1)->save();
    drupal_static_reset('drupal_add_js');

    // Add two JavaScript files to the current request and build the cache.
    drupal_add_js('core/misc/ajax.js');
    drupal_add_js('core/misc/autocomplete.js');

    $js_items = drupal_add_js();
    drupal_build_js_cache(array(
      'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
      'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
    ));

    // Store the expected key for the first item in the cache.
    $cache = array_keys(variable_get('drupal_js_cache_files', array()));
    $expected_key = $cache[0];

    // Reset variables and add a file in a different scope first.
    variable_del('drupal_js_cache_files');
    drupal_static_reset('drupal_add_js');
    drupal_add_js('some/custom/javascript_file.js', array('scope' => 'footer'));
    drupal_add_js('core/misc/ajax.js');
    drupal_add_js('core/misc/autocomplete.js');

    // Rebuild the cache.
    $js_items = drupal_add_js();
    drupal_build_js_cache(array(
      'core/misc/ajax.js' => $js_items['core/misc/ajax.js'],
      'core/misc/autocomplete.js' => $js_items['core/misc/autocomplete.js']
    ));

    // Compare the expected key for the first file to the current one.
    $cache = array_keys(variable_get('drupal_js_cache_files', array()));
    $key = $cache[0];
    $this->assertEqual($key, $expected_key, 'JavaScript aggregation is not affected by ordering in different scopes.');
  }

  /**
   * Test JavaScript ordering.
   */
  function testRenderOrder() {
    // Add a bunch of JavaScript in strange ordering.
    drupal_add_js('(function($){alert("Weight 5 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => 5));
    drupal_add_js('(function($){alert("Weight 0 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));
    drupal_add_js('(function($){alert("Weight 0 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));
    drupal_add_js('(function($){alert("Weight -8 #1");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    drupal_add_js('(function($){alert("Weight -8 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    drupal_add_js('(function($){alert("Weight -8 #3");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    drupal_add_js('http://example.com/example.js?Weight -5 #1', array('type' => 'external', 'scope' => 'footer', 'weight' => -5));
    drupal_add_js('(function($){alert("Weight -8 #4");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => -8));
    drupal_add_js('(function($){alert("Weight 5 #2");})(jQuery);', array('type' => 'inline', 'scope' => 'footer', 'weight' => 5));
    drupal_add_js('(function($){alert("Weight 0 #3");})(jQuery);', array('type' => 'inline', 'scope' => 'footer'));

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
    $this->assertIdentical($result, $expected, t('JavaScript is added in the expected weight order.'));
  }

  /**
   * Test rendering the JavaScript with a file's weight above jQuery's.
   */
  function testRenderDifferentWeight() {
    // JavaScript files are sorted first by group, then by the 'every_page'
    // flag, then by weight (see drupal_sort_css_js()), so to test the effect of
    // weight, we need the other two options to be the same.
    drupal_add_js('core/misc/collapse.js', array('group' => JS_LIBRARY, 'every_page' => TRUE, 'weight' => -21));
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'core/misc/collapse.js') < strpos($javascript, 'core/misc/jquery.js'), t('Rendering a JavaScript file above jQuery.'));
  }

  /**
   * Test altering a JavaScript's weight via hook_js_alter().
   *
   * @see simpletest_js_alter()
   */
  function testAlter() {
    // Add both tableselect.js and simpletest.js, with a larger weight on SimpleTest.
    drupal_add_js('core/misc/tableselect.js');
    drupal_add_js(drupal_get_path('module', 'simpletest') . '/simpletest.js', array('weight' => 9999));

    // Render the JavaScript, testing if simpletest.js was altered to be before
    // tableselect.js. See simpletest_js_alter() to see where this alteration
    // takes place.
    $javascript = drupal_get_js();
    $this->assertTrue(strpos($javascript, 'simpletest.js') < strpos($javascript, 'core/misc/tableselect.js'), t('Altering JavaScript weight through the alter hook.'));
  }

  /**
   * Adds a library to the page and tests for both its JavaScript and its CSS.
   */
  function testLibraryRender() {
    $result = drupal_add_library('system', 'farbtastic');
    $this->assertTrue($result !== FALSE, t('Library was added without errors.'));
    $scripts = drupal_get_js();
    $styles = drupal_get_css();
    $this->assertTrue(strpos($scripts, 'core/misc/farbtastic/farbtastic.js'), t('JavaScript of library was added to the page.'));
    $this->assertTrue(strpos($styles, 'core/misc/farbtastic/farbtastic.css'), t('Stylesheet of library was added to the page.'));
  }

  /**
   * Adds a JavaScript library to the page and alters it.
   *
   * @see common_test_library_info_alter()
   */
  function testLibraryAlter() {
    // Verify that common_test altered the title of Farbtastic.
    $library = drupal_get_library('system', 'farbtastic');
    $this->assertEqual($library['title'], 'Farbtastic: Altered Library', t('Registered libraries were altered.'));

    // common_test_library_info_alter() also added a dependency on jQuery Form.
    drupal_add_library('system', 'farbtastic');
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/misc/jquery.form.js'), t('Altered library dependencies are added to the page.'));
  }

  /**
   * Tests that multiple modules can implement the same library.
   *
   * @see common_test_library_info()
   */
  function testLibraryNameConflicts() {
    $farbtastic = drupal_get_library('common_test', 'farbtastic');
    $this->assertEqual($farbtastic['title'], 'Custom Farbtastic Library', t('Alternative libraries can be added to the page.'));
  }

  /**
   * Tests non-existing libraries.
   */
  function testLibraryUnknown() {
    $result = drupal_get_library('unknown', 'unknown');
    $this->assertFalse($result, t('Unknown library returned FALSE.'));
    drupal_static_reset('drupal_get_library');

    $result = drupal_add_library('unknown', 'unknown');
    $this->assertFalse($result, t('Unknown library returned FALSE.'));
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'unknown') === FALSE, t('Unknown library was not added to the page.'));
  }

  /**
   * Tests the addition of libraries through the #attached['library'] property.
   */
  function testAttachedLibrary() {
    $element['#attached']['library'][] = array('system', 'farbtastic');
    drupal_render($element);
    $scripts = drupal_get_js();
    $this->assertTrue(strpos($scripts, 'core/misc/farbtastic/farbtastic.js'), t('The attached_library property adds the additional libraries.'));
  }

  /**
   * Tests retrieval of libraries via drupal_get_library().
   */
  function testGetLibrary() {
    // Retrieve all libraries registered by a module.
    $libraries = drupal_get_library('common_test');
    $this->assertTrue(isset($libraries['farbtastic']), t('Retrieved all module libraries.'));
    // Retrieve all libraries for a module not implementing hook_library_info().
    // Note: This test installs Locale module.
    $libraries = drupal_get_library('locale');
    $this->assertEqual($libraries, array(), t('Retrieving libraries from a module not implementing hook_library_info() returns an emtpy array.'));

    // Retrieve a specific library by module and name.
    $farbtastic = drupal_get_library('common_test', 'farbtastic');
    $this->assertEqual($farbtastic['version'], '5.3', t('Retrieved a single library.'));
    // Retrieve a non-existing library by module and name.
    $farbtastic = drupal_get_library('common_test', 'foo');
    $this->assertIdentical($farbtastic, FALSE, t('Retrieving a non-existing library returns FALSE.'));
  }

  /**
   * Tests that the query string remains intact when adding JavaScript files
   *  that have query string parameters.
   */
  function testAddJsFileWithQueryString() {
    $this->drupalGet('common-test/query-string');
    $query_string = variable_get('css_js_query_string', '0');
    $this->assertRaw(drupal_get_path('module', 'node') . '/node.js?' . $query_string, t('Query string was appended correctly to js.'));
  }
}
