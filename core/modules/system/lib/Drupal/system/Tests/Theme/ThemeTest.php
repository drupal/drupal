<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\ThemeTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests low-level theme functions.
 */
class ThemeTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Theme API',
      'description' => 'Test low-level theme functions.',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp('theme_test');
    theme_enable(array('test_theme'));
  }

  /**
   * Test function theme_get_suggestions() for SA-CORE-2009-003.
   */
  function testThemeSuggestions() {
    // Set the front page as something random otherwise the CLI
    // test runner fails.
    variable_set('site_frontpage', 'nobody-home');
    $args = array('node', '1', 'edit');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1', 'page__node__edit'), t('Found expected node edit page suggestions'));
    // Check attack vectors.
    $args = array('node', '\\1');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), t('Removed invalid \\ from suggestions'));
    $args = array('node', '1/');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), t('Removed invalid / from suggestions'));
    $args = array('node', "1\0");
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, array('page__node', 'page__node__%', 'page__node__1'), t('Removed invalid \\0 from suggestions'));
    // Define path with hyphens to be used to generate suggestions.
    $args = array('node', '1', 'hyphen-path');
    $result = array('page__node', 'page__node__%', 'page__node__1', 'page__node__hyphen_path');
    $suggestions = theme_get_suggestions($args, 'page');
    $this->assertEqual($suggestions, $result, t('Found expected page suggestions for paths containing hyphens.'));
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
   * Ensure page-front template suggestion is added when on front page.
   */
  function testFrontPageThemeSuggestion() {
    $original_path = _current_path();
    // Set the current path to node because theme_get_suggestions() will query
    // it to see if we are on the front page.
    variable_set('site_frontpage', 'node');
    _current_path('node');
    $suggestions = theme_get_suggestions(array('node'), 'page');
    // Set it back to not annoy the batch runner.
    _current_path($original_path);
    $this->assertTrue(in_array('page__front', $suggestions), t('Front page template was suggested.'));
  }

  /**
   * Ensures theme hook_*_alter() implementations can run before anything is rendered.
   */
  function testAlter() {
    $this->drupalGet('theme-test/alter');
    $this->assertText('The altered data is test_theme_theme_test_alter_alter was invoked.', t('The theme was able to implement an alter hook during page building before anything was rendered.'));
  }

  /**
   * Ensures a theme's .info file is able to override a module CSS file from being added to the page.
   *
   * @see test_theme.info
   */
  function testCSSOverride() {
    // Reuse the same page as in testPreprocessForSuggestions(). We're testing
    // what is output to the HTML HEAD based on what is in a theme's .info file,
    // so it doesn't matter what page we get, as long as it is themed with the
    // test theme. First we test with CSS aggregation disabled.
    $config = config('system.performance');
    $config->set('preprocess_css', 0);
    $config->save();
    $this->drupalGet('theme-test/suggestion');
    $this->assertNoText('system.base.css', t('The theme\'s .info file is able to override a module CSS file from being added to the page.'));

    // Also test with aggregation enabled, simply ensuring no PHP errors are
    // triggered during drupal_build_css_cache() when a source file doesn't
    // exist. Then allow remaining tests to continue with aggregation disabled
    // by default.
    $config->set('preprocess_css', 1);
    $config->save();
    $this->drupalGet('theme-test/suggestion');
    $config->set('preprocess_css', 0);
    $config->save();
  }

  /**
   * Ensures a themes template is overrideable based on the 'template' filename.
   */
  function testTemplateOverride() {
    variable_set('theme_default', 'test_theme');
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden.', t('Template overridden by defined \'template\' filename.'));
  }

  /**
   * Test the list_themes() function.
   */
  function testListThemes() {
    $themes = list_themes();
    // Check if drupal_theme_access() retrieves enabled themes properly from list_themes().
    $this->assertTrue(drupal_theme_access('test_theme'), t('Enabled theme detected'));
    // Check if list_themes() returns disabled themes.
    $this->assertTrue(array_key_exists('test_basetheme', $themes), t('Disabled theme detected'));
    // Check for base theme and subtheme lists.
    $base_theme_list = array('test_basetheme' => 'Theme test base theme');
    $sub_theme_list = array('test_subtheme' => 'Theme test subtheme');
    $this->assertIdentical($themes['test_basetheme']->sub_themes, $sub_theme_list, t('Base theme\'s object includes list of subthemes.'));
    $this->assertIdentical($themes['test_subtheme']->base_themes, $base_theme_list, t('Subtheme\'s object includes list of base themes.'));
    // Check for theme engine in subtheme.
    $this->assertIdentical($themes['test_subtheme']->engine, 'phptemplate', t('Subtheme\'s object includes the theme engine.'));
    // Check for theme engine prefix.
    $this->assertIdentical($themes['test_basetheme']->prefix, 'phptemplate', t('Base theme\'s object includes the theme engine prefix.'));
    $this->assertIdentical($themes['test_subtheme']->prefix, 'phptemplate', t('Subtheme\'s object includes the theme engine prefix.'));
  }

  /**
   * Test the theme_get_setting() function.
   */
  function testThemeGetSetting() {
    $GLOBALS['theme_key'] = 'test_theme';
    $this->assertIdentical(theme_get_setting('theme_test_setting'), 'default value', t('theme_get_setting() uses the default theme automatically.'));
    $this->assertNotEqual(theme_get_setting('subtheme_override', 'test_basetheme'), theme_get_setting('subtheme_override', 'test_subtheme'), t('Base theme\'s default settings values can be overridden by subtheme.'));
    $this->assertIdentical(theme_get_setting('basetheme_only', 'test_subtheme'), 'base theme value', t('Base theme\'s default settings values are inherited by subtheme.'));
  }

  /**
   * Ensures the theme registry is rebuilt when modules are disabled/enabled.
   */
  function testRegistryRebuild() {
    $this->assertIdentical(theme('theme_test_foo', array('foo' => 'a')), 'a', 'The theme registry contains theme_test_foo.');

    module_disable(array('theme_test'), FALSE);
    $this->assertIdentical(theme('theme_test_foo', array('foo' => 'b')), '', 'The theme registry does not contain theme_test_foo, because the module is disabled.');

    module_enable(array('theme_test'), FALSE);
    $this->assertIdentical(theme('theme_test_foo', array('foo' => 'c')), 'c', 'The theme registry contains theme_test_foo again after re-enabling the module.');
  }
}
