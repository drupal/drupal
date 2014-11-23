<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\ThemeTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\simpletest\WebTestBase;
use Drupal\test_theme\ThemeClass;

/**
 * Tests low-level theme functions.
 *
 * @group Theme
 */
class ThemeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test', 'node');

  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_handler')->install(array('test_theme'));
  }

  /**
   * Test attribute merging.
   *
   * Render arrays that use a render element and templates (and hence call
   * template_preprocess()) must ensure the attributes at different occasions
   * are all merged correctly:
   *   - $variables['attributes'] as passed in to _theme()
   *   - the render element's #attributes
   *   - any attributes set in the template's preprocessing function
   */
  function testAttributeMerging() {
    $theme_test_render_element = array(
      'elements' => array(
        '#attributes' => array('data-foo' => 'bar'),
      ),
      'attributes' => array(
        'id' => 'bazinga',
      ),
    );
    $this->assertThemeOutput('theme_test_render_element', $theme_test_render_element, '<div id="bazinga" data-foo="bar" data-variables-are-preprocessed></div>' . "\n");
  }

  /**
   * Test that _theme() returns expected data types.
   */
  function testThemeDataTypes() {
    // theme_test_false is an implemented theme hook so \Drupal::theme() service should
    // return a string, even though the theme function itself can return anything.
    $foos = array('null' => NULL, 'false' => FALSE, 'integer' => 1, 'string' => 'foo');
    foreach ($foos as $type => $example) {
      $output = \Drupal::theme()->render('theme_test_foo', array('foo' => $example));
      $this->assertTrue(is_string($output), format_string('\Drupal::theme() returns a string for data type !type.', array('!type' => $type)));
    }

    // suggestionnotimplemented is not an implemented theme hook so \Drupal::theme() service
    // should return FALSE instead of a string.
    $output = \Drupal::theme()->render(array('suggestionnotimplemented'), array());
    $this->assertIdentical($output, FALSE, '\Drupal::theme() returns FALSE when a hook suggestion is not implemented.');
  }

  /**
   * Test function theme_get_suggestions() for SA-CORE-2009-003.
   */
  function testThemeSuggestions() {
    // Set the front page as something random otherwise the CLI
    // test runner fails.
    \Drupal::config('system.site')->set('page.front', 'nobody-home')->save();
    $args = array('node', '1', 'edit');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1', 'page__node__edit'), 'Found expected node edit page suggestions');
    // Check attack vectors.
    $args = array('node', '\\1');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), 'Removed invalid \\ from suggestions');
    $args = array('node', '1/');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), 'Removed invalid / from suggestions');
    $args = array('node', "1\0");
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), 'Removed invalid \\0 from suggestions');
    // Define path with hyphens to be used to generate suggestions.
    $args = array('node', '1', 'hyphen-path');
    $result = array('page__node', 'page__node__%', 'page__node__1', 'page__node__hyphen_path');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, $result, 'Found expected page suggestions for paths containing hyphens.');
  }

  /**
   * Ensures preprocess functions run even for suggestion implementations.
   *
   * The theme hook used by this test has its base preprocess function in a
   * separate file, so this test also ensures that that file is correctly loaded
   * when needed.
   */
  function testPreprocessForSuggestions() {
    // Test with both an unprimed and primed theme registry.
    drupal_theme_rebuild();
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('theme-test/suggestion');
      $this->assertText('Theme hook implementor=test_theme_theme_test__suggestion(). Foo=template_preprocess_theme_test', 'Theme hook suggestion ran with data available from a preprocess function for the base hook.');
    }
  }

  /**
   * Tests the priority of some theme negotiators.
   */
  public function testNegotiatorPriorities() {
    $this->drupalGet('theme-test/priority');

    // Ensure that the custom theme negotiator was not able to set the theme.

    $this->assertNoText('Theme hook implementor=test_theme_theme_test__suggestion(). Foo=template_preprocess_theme_test', 'Theme hook suggestion ran with data available from a preprocess function for the base hook.');
  }

  /**
   * Ensures that non-HTML requests never initialize themes.
   */
  public function testThemeOnNonHtmlRequest() {
    $this->drupalGet('theme-test/non-html');
    $json = Json::decode($this->getRawContent());
    $this->assertFalse($json['theme_initialized']);
  }

  /**
   * Ensure page-front template suggestion is added when on front page.
   */
  function testFrontPageThemeSuggestion() {
    $original_path = _current_path();
    // Set the current path to node because theme_get_suggestions() will query
    // it to see if we are on the front page.
    \Drupal::config('system.site')->set('page.front', 'node')->save();
    _current_path('node');
    $suggestions = theme_get_suggestions(array('node'), 'page');
    // Set it back to not annoy the batch runner.
    _current_path($original_path);
    $this->assertTrue(in_array('page__front', $suggestions), 'Front page template was suggested.');
  }

  /**
   * Ensures a theme's .info.yml file is able to override a module CSS file from being added to the page.
   *
   * @see test_theme.info.yml
   */
  function testCSSOverride() {
    // Reuse the same page as in testPreprocessForSuggestions(). We're testing
    // what is output to the HTML HEAD based on what is in a theme's .info.yml
    // file, so it doesn't matter what page we get, as long as it is themed with
    // the test theme. First we test with CSS aggregation disabled.
    $config = \Drupal::config('system.performance');
    $config->set('css.preprocess', 0);
    $config->save();
    $this->drupalGet('theme-test/suggestion');
    $this->assertNoText('system.module.css', 'The theme\'s .info.yml file is able to override a module CSS file from being added to the page.');

    // Also test with aggregation enabled, simply ensuring no PHP errors are
    // triggered during drupal_build_css_cache() when a source file doesn't
    // exist. Then allow remaining tests to continue with aggregation disabled
    // by default.
    $config->set('css.preprocess', 1);
    $config->save();
    $this->drupalGet('theme-test/suggestion');
    $config->set('css.preprocess', 0);
    $config->save();
  }

  /**
   * Ensures a themes template is overrideable based on the 'template' filename.
   */
  function testTemplateOverride() {
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden.', 'Template overridden by defined \'template\' filename.');
  }

  /**
   * Ensures a theme template can override a theme function.
   */
  function testFunctionOverride() {
    $this->drupalGet('theme-test/function-template-overridden');
    $this->assertText('Success: Template overrides theme function.', 'Theme function overridden by test_theme template.');
  }

  /**
   * Test the list_themes() function.
   */
  function testListThemes() {
    $theme_handler = $this->container->get('theme_handler');
    $theme_handler->install(array('test_subtheme'));
    $themes = $theme_handler->listInfo();

    // Check if drupal_theme_access() retrieves installed themes properly from
    // list_themes().
    $this->assertTrue(drupal_theme_access('test_theme'), 'Installed theme detected');

    // Check for base theme and subtheme lists.
    $base_theme_list = array('test_basetheme' => 'Theme test base theme');
    $sub_theme_list = array('test_subtheme' => 'Theme test subtheme');
    $this->assertIdentical($themes['test_basetheme']->sub_themes, $sub_theme_list, 'Base theme\'s object includes list of subthemes.');
    $this->assertIdentical($themes['test_subtheme']->base_themes, $base_theme_list, 'Subtheme\'s object includes list of base themes.');
    // Check for theme engine in subtheme.
    $this->assertIdentical($themes['test_subtheme']->engine, 'twig', 'Subtheme\'s object includes the theme engine.');
    // Check for theme engine prefix.
    $this->assertIdentical($themes['test_basetheme']->prefix, 'twig', 'Base theme\'s object includes the theme engine prefix.');
    $this->assertIdentical($themes['test_subtheme']->prefix, 'twig', 'Subtheme\'s object includes the theme engine prefix.');
  }

  /**
   * Test the theme_get_setting() function.
   */
  function testThemeGetSetting() {
    $this->container->get('theme_handler')->install(array('test_subtheme'));
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('test_theme'));
    $this->assertIdentical(theme_get_setting('theme_test_setting'), 'default value', 'theme_get_setting() uses the default theme automatically.');
    $this->assertNotEqual(theme_get_setting('subtheme_override', 'test_basetheme'), theme_get_setting('subtheme_override', 'test_subtheme'), 'Base theme\'s default settings values can be overridden by subtheme.');
    $this->assertIdentical(theme_get_setting('basetheme_only', 'test_subtheme'), 'base theme value', 'Base theme\'s default settings values are inherited by subtheme.');
  }

  /**
   * Tests child element rendering for 'render element' theme hooks.
   */
  function testDrupalRenderChildren() {
    $element = array(
      '#theme' => 'theme_test_render_element_children',
      'child' => array(
        '#markup' => 'Foo',
      ),
    );
    $this->assertThemeOutput('theme_test_render_element_children', $element, 'Foo', 'drupal_render() avoids #theme recursion loop when rendering a render element.');

    $element = array(
      '#theme_wrappers' => array('theme_test_render_element_children'),
      'child' => array(
        '#markup' => 'Foo',
      ),
    );
    $this->assertThemeOutput('theme_test_render_element_children', $element, 'Foo', 'drupal_render() avoids #theme_wrappers recursion loop when rendering a render element.');
  }

  /**
   * Tests theme can provide classes.
   */
  function testClassLoading() {
    new ThemeClass();
  }

  /**
   * Tests drupal_find_theme_templates().
   */
  public function testFindThemeTemplates() {
    $registry = $this->container->get('theme.registry')->get();
    $templates = drupal_find_theme_templates($registry, '.html.twig', drupal_get_path('theme', 'test_theme'));
    $this->assertEqual($templates['node__1']['template'], 'node--1', 'Template node--1.tpl.twig was found in test_theme.');
  }

  /**
   * Tests that the page variable is not prematurely flattened.
   *
   * Some modules check the page array in template_preprocess_html(), so we
   * ensure that it has not been rendered prematurely.
   */
  function testPreprocessHtml() {
    $this->drupalGet('');
    $attributes = $this->xpath('/html/body[@theme_test_page_variable="Page variable is an array."]');
    $this->assertTrue(count($attributes) == 1, 'In template_preprocess_html(), the page variable is still an array (not rendered yet).');
    $this->assertText('theme test page bottom markup', 'Modules are able to set the page bottom region.');
  }

  /**
   * Tests that region attributes can be manipulated via preprocess functions.
   */
  function testRegionClass() {
    \Drupal::service('module_installer')->install(array('block', 'theme_region_test'));

    // Place a block.
    $this->drupalPlaceBlock('system_main_block');
    $this->drupalGet('');
    $elements = $this->cssSelect(".region-sidebar-first.new_class");
    $this->assertEqual(count($elements), 1, 'New class found.');
  }

}
