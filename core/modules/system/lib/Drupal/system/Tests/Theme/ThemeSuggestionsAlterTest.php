<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ThemeSuggestionsAlterTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests theme suggestion alter hooks.
 */
class ThemeSuggestionsAlterTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'Theme suggestions alter',
      'description' => 'Test theme suggestion alter hooks.',
      'group' => 'Theme',
    );
  }

  function setUp() {
    parent::setUp();
    theme_enable(array('test_theme'));
  }

  /**
   * Tests that hooks to provide theme suggestions work.
   */
  function testTemplateSuggestions() {
    $this->drupalGet('theme-test/suggestion-provided');
    $this->assertText('Template for testing suggestions provided by the module declaring the theme hook.');

    // Enable test_theme, it contains a template suggested by theme_test.module
    // in theme_test_theme_suggestions_theme_test_suggestion_provided().
    config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('theme-test/suggestion-provided');
    $this->assertText('Template overridden based on suggestion provided by the module declaring the theme hook.');
  }

  /**
   * Tests that theme suggestion alter hooks work for templates.
   */
  function testTemplateSuggestionsAlter() {
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Original template.');

    // Enable test_theme and test that themes can alter template suggestions.
    config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by the test_theme theme.');

    // Enable the theme_suggestions_test module to test modules implementing
    // suggestions alter hooks.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by a module.');
  }

  /**
   * Tests that theme suggestion alter hooks work for specific theme calls.
   */
  function testSpecificSuggestionsAlter() {
    // Test that the default template is rendered.
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $this->assertText('Template for testing specific theme calls.');

    config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    // Test a specific theme call similar to '#theme' => 'node__article'.
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $this->assertText('Template matching the specific theme call.');
    $this->assertText('theme_test_specific_suggestions__variant', 'Specific theme call is added to the suggestions array.');

    // Ensure that the base hook is used to determine the suggestion alter hook.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $this->assertText('Template overridden based on suggestion alter hook determined by the base hook.');
    $this->assertTrue(strpos($this->drupalGetContent(), 'theme_test_specific_suggestions__variant') < strpos($this->drupalGetContent(), 'theme_test_specific_suggestions__variant__foo'), 'Specific theme call is added to the suggestions array before the suggestions alter hook.');
  }

  /**
   * Tests that theme suggestion alter hooks work for theme functions.
   */
  function testThemeFunctionSuggestionsAlter() {
    $this->drupalGet('theme-test/function-suggestion-alter');
    $this->assertText('Original theme function.');

    // Enable test_theme and test that themes can alter theme suggestions.
    config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/function-suggestion-alter');
    $this->assertText('Theme function overridden based on new theme suggestion provided by the test_theme theme.');

    // Enable the theme_suggestions_test module to test modules implementing
    // suggestions alter hooks.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->drupalGet('theme-test/function-suggestion-alter');
    $this->assertText('Theme function overridden based on new theme suggestion provided by a module.');
  }

}
