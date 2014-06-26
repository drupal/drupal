<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\ThemeSuggestionsAlterTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\Component\Utility\Xss;
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
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    $this->drupalGet('theme-test/suggestion-provided');
    $this->assertText('Template overridden based on suggestion provided by the module declaring the theme hook.');
  }

  /**
   * Tests hook_theme_suggestions_alter().
   */
  function testGeneralSuggestionsAlter() {
    $this->drupalGet('theme-test/general-suggestion-alter');
    $this->assertText('Original template for testing hook_theme_suggestions_alter().');

    // Enable test_theme and test that themes can alter template suggestions.
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/general-suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_alter().');

    // Enable the theme_suggestions_test module to test modules implementing
    // suggestions alter hooks.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();
    $this->drupalGet('theme-test/general-suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by a module via hook_theme_suggestions_alter().');
  }

  /**
   * Tests that theme suggestion alter hooks work for templates.
   */
  function testTemplateSuggestionsAlter() {
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Original template for testing hook_theme_suggestions_HOOK_alter().');

    // Enable test_theme and test that themes can alter template suggestions.
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by the test_theme theme via hook_theme_suggestions_HOOK_alter().');

    // Enable the theme_suggestions_test module to test modules implementing
    // suggestions alter hooks.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();
    $this->drupalGet('theme-test/suggestion-alter');
    $this->assertText('Template overridden based on new theme suggestion provided by a module via hook_theme_suggestions_HOOK_alter().');
  }

  /**
   * Tests that theme suggestion alter hooks work for specific theme calls.
   */
  function testSpecificSuggestionsAlter() {
    // Test that the default template is rendered.
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $this->assertText('Template for testing specific theme calls.');

    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();

    // Test a specific theme call similar to '#theme' => 'node__article'.
    $this->drupalGet('theme-test/specific-suggestion-alter');
    $this->assertText('Template matching the specific theme call.');
    $this->assertText('theme_test_specific_suggestions__variant', 'Specific theme call is added to the suggestions array.');

    // Ensure that the base hook is used to determine the suggestion alter hook.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();
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
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/function-suggestion-alter');
    $this->assertText('Theme function overridden based on new theme suggestion provided by the test_theme theme.');

    // Enable the theme_suggestions_test module to test modules implementing
    // suggestions alter hooks.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();
    $this->drupalGet('theme-test/function-suggestion-alter');
    $this->assertText('Theme function overridden based on new theme suggestion provided by a module.');
  }

  /**
   * Tests that theme suggestion alter hooks work with theme hook includes.
   */
  public function testSuggestionsAlterInclude() {
    // Check the original theme output.
    $this->drupalGet('theme-test/suggestion-alter-include');
    $this->assertText('Original function before altering theme suggestions.');

    // Enable theme_suggestions_test module and make two requests to make sure
    // the include file is always loaded. The file will always be included for
    // the first request because the theme registry is being rebuilt.
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();
    $this->drupalGet('theme-test/suggestion-alter-include');
    $this->assertText('Function suggested via suggestion alter hook found in include file.', 'Include file loaded for initial request.');
    $this->drupalGet('theme-test/suggestion-alter-include');
    $this->assertText('Function suggested via suggestion alter hook found in include file.', 'Include file loaded for second request.');
  }

  /**
   * Tests execution order of theme suggestion alter hooks.
   *
   * hook_theme_suggestions_alter() should fire before
   * hook_theme_suggestions_HOOK_alter() within an extension (module or theme).
   */
  function testExecutionOrder() {
    // Enable our test theme and module.
    \Drupal::config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    \Drupal::moduleHandler()->install(array('theme_suggestions_test'));
    $this->resetAll();

    // Send two requests so that we get all the messages we've set via
    // drupal_set_message().
    $this->drupalGet('theme-test/suggestion-alter');
    // Ensure that the order is first by extension, then for a given extension,
    // the hook-specific one after the generic one.
    $expected = array(
      'theme_suggestions_test_theme_suggestions_alter() executed.',
      'theme_suggestions_test_theme_suggestions_theme_test_suggestions_alter() executed.',
      'theme_test_theme_suggestions_alter() executed.',
      'theme_test_theme_suggestions_theme_test_suggestions_alter() executed.',
      'test_theme_theme_suggestions_alter() executed.',
      'test_theme_theme_suggestions_theme_test_suggestions_alter() executed.',
    );
    $content = preg_replace('/\s+/', ' ', Xss::filter($this->content, array()));
    $this->assert(strpos($content, implode(' ', $expected)) !== FALSE, 'Suggestion alter hooks executed in the expected order.');
  }

}
