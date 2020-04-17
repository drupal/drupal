<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Tests low-level theme functions.
 *
 * @group Theme
 */
class ThemeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['theme_test', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['test_theme']);
  }

  /**
   * Ensures preprocess functions run even for suggestion implementations.
   *
   * The theme hook used by this test has its base preprocess function in a
   * separate file, so this test also ensures that the file is correctly loaded
   * when needed.
   */
  public function testPreprocessForSuggestions() {
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
    $json = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertFalse($json['theme_initialized']);
  }

  /**
   * Ensure page-front template suggestion is added when on front page.
   */
  public function testFrontPageThemeSuggestion() {
    // Set the current route to user.login because theme_get_suggestions() will
    // query it to see if we are on the front page.
    $request = Request::create('/user/login');
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, 'user.login');
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('/user/login'));
    \Drupal::requestStack()->push($request);
    $this->config('system.site')->set('page.front', '/user/login')->save();
    $suggestions = theme_get_suggestions(['user', 'login'], 'page');
    // Set it back to not annoy the batch runner.
    \Drupal::requestStack()->pop();
    $this->assertTrue(in_array('page__front', $suggestions), 'Front page template was suggested.');
  }

  /**
   * Tests theme can provide classes.
   */
  public function testClassLoading() {
    // Install test theme and set it as default.
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->resetAll();
    // Visit page controller and confirm that the theme class is loaded.
    $this->drupalGet('/theme-test/test-theme-class');
    $this->assertText('Loading ThemeClass was successful.');
  }

  /**
   * Ensures a theme's .info.yml file is able to override a module CSS file from being added to the page.
   *
   * @see test_theme.info.yml
   */
  public function testCSSOverride() {
    // Reuse the same page as in testPreprocessForSuggestions(). We're testing
    // what is output to the HTML HEAD based on what is in a theme's .info.yml
    // file, so it doesn't matter what page we get, as long as it is themed with
    // the test theme. First we test with CSS aggregation disabled.
    $config = $this->config('system.performance');
    $config->set('css.preprocess', 0);
    $config->save();
    $this->drupalGet('theme-test/suggestion');
    // We add a "?" to the assertion, because drupalSettings may include
    // information about the file; we only really care about whether it appears
    // in a LINK or STYLE tag, for which Drupal always adds a query string for
    // cache control.
    $this->assertSession()->responseNotContains('js.module.css?');

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
   * Ensures a themes template is overridable based on the 'template' filename.
   */
  public function testTemplateOverride() {
    $this->config('system.theme')
      ->set('default', 'test_theme')
      ->save();
    $this->drupalGet('theme-test/template-test');
    $this->assertText('Success: Template overridden.', 'Template overridden by defined \'template\' filename.');
  }

  /**
   * Ensures a theme template can override a theme function.
   */
  public function testFunctionOverride() {
    $this->drupalGet('theme-test/function-template-overridden');
    $this->assertText('Success: Template overrides theme function.', 'Theme function overridden by test_theme template.');
  }

  /**
   * Tests that the page variable is not prematurely flattened.
   *
   * Some modules check the page array in template_preprocess_html(), so we
   * ensure that it has not been rendered prematurely.
   */
  public function testPreprocessHtml() {
    $this->drupalGet('');
    $attributes = $this->xpath('/body[@theme_test_page_variable="Page variable is an array."]');
    $this->assertTrue(count($attributes) == 1, 'In template_preprocess_html(), the page variable is still an array (not rendered yet).');
    $this->assertText('theme test page bottom markup', 'Modules are able to set the page bottom region.');
  }

  /**
   * Tests that region attributes can be manipulated via preprocess functions.
   */
  public function testRegionClass() {
    \Drupal::service('module_installer')->install(['block', 'theme_region_test']);

    // Place a block.
    $this->drupalPlaceBlock('system_main_block');
    $this->drupalGet('');
    $elements = $this->cssSelect(".region-sidebar-first.new_class");
    $this->assertEqual(count($elements), 1, 'New class found.');
  }

  /**
   * Ensures suggestion preprocess functions run for default implementations.
   *
   * The theme hook used by this test has its base preprocess function in a
   * separate file, so this test also ensures that the file is correctly loaded
   * when needed.
   */
  public function testSuggestionPreprocessForDefaults() {
    $this->config('system.theme')->set('default', 'test_theme')->save();
    // Test with both an unprimed and primed theme registry.
    drupal_theme_rebuild();
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('theme-test/preprocess-suggestions');
      $items = $this->cssSelect('.suggestion');
      $expected_values = [
        'Suggestion',
        'Kitten',
        'Monkey',
        'Kitten',
        'Flamingo',
      ];
      foreach ($expected_values as $key => $value) {
        $this->assertEqual((string) $value, $items[$key]->getText());
      }
    }
  }

}
