<?php

namespace Drupal\Tests\views\Functional;

/**
 * Tests the Views theme integration.
 *
 * We test against test_basetheme and test_subtheme provided by theme_test
 *
 * @group views
 */
class ViewsThemeIntegrationTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_page_display'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';


  /**
   * {@inheritdoc}
   *
   * We need theme_test for testing against test_basetheme and test_subtheme.
   *
   * @var array
   *
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'theme_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->enableViewsTestModule();
  }

  /**
   * Tests for exceptions and successful execution of hook_views_pre_render()
   * and hook_views_post_render() in theme and subtheme.
   */
  public function testThemedViewPage() {

    \Drupal::service('theme_installer')->install(['test_basetheme', 'test_subtheme']);

    // Make base theme default then test for hook invocations.
    $this->config('system.theme')
      ->set('default', 'test_basetheme')
      ->save();
    $this->assertEqual($this->config('system.theme')->get('default'), 'test_basetheme');

    // Make sure a views rendered page is touched.
    $this->drupalGet('test_page_display_200');

    $this->assertRaw("test_basetheme_views_pre_render");
    $this->assertRaw("test_basetheme_views_post_render");

    // Make sub theme default to test for hook invocation
    // from both sub and base theme.
    $this->config('system.theme')
      ->set('default', 'test_subtheme')
      ->save();
    $this->assertEqual($this->config('system.theme')->get('default'), 'test_subtheme');

    // Make sure a views rendered page is touched.
    $this->drupalGet('test_page_display_200');

    $this->assertRaw("test_subtheme_views_pre_render");
    $this->assertRaw("test_subtheme_views_post_render");

    $this->assertRaw("test_basetheme_views_pre_render");
    $this->assertRaw("test_basetheme_views_post_render");

    // Verify that the views group title is added.
    $this->assertRaw('<em class="placeholder">' . count($this->dataSet()) . '</em> items found.');
  }

}
