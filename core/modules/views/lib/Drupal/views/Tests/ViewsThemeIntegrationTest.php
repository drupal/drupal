<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsThemeIntegrationTest.
 */

namespace Drupal\views\Tests;

/**
 * As views uses a lot of theme related functionality we need to test these too.
 *
 * We test against test_basetheme and test_subtheme provided by theme_test
 */
class ViewsThemeIntegrationTest extends ViewTestBase {

 /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_page_display');


  /**
   * Used by WebTestBase::setup()
   *
   * We need theme_test for testing against test_basetheme and test_subtheme.
   *
   * @var array
   *
   * @see \Drupal\simpletest\WebTestBase::setup()
   */
  public static $modules = array('views', 'theme_test');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Views theme integration test',
      'description' => 'Tests the Views theme integration.',
      'group' => 'Views',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests for exceptions and successful execution of hook_views_pre_render()
   * and hook_views_post_render() in theme and subtheme.
   */
  public function testThemedViewPage() {

    \Drupal::service('theme_handler')->enable(array('test_basetheme', 'test_subtheme'));

    // Make base theme default then test for hook invocations.
    \Drupal::config('system.theme')
        ->set('default', 'test_basetheme')
        ->save();
    $this->assertEqual(\Drupal::config('system.theme')->get('default'), 'test_basetheme');

    // Make sure a views rendered page is touched.
    $this->drupalGet('test_page_display_200');

    $this->assertRaw("test_basetheme_views_pre_render", "Views title changed by test_basetheme.test_basetheme_views_pre_render");
    $this->assertRaw("test_basetheme_views_post_render", "Views title changed by test_basetheme.test_basetheme_views_post_render");

    // Make sub theme default to test for hook invocation
    // from both sub and base theme.
    \Drupal::config('system.theme')
        ->set('default', 'test_subtheme')
        ->save();
    $this->assertEqual(\Drupal::config('system.theme')->get('default'), 'test_subtheme');

    // Make sure a views rendered page is touched.
    $this->drupalGet('test_page_display_200');

    $this->assertRaw("test_subtheme_views_pre_render", "Views title changed by test_usetheme.test_subtheme_views_pre_render");
    $this->assertRaw("test_subtheme_views_post_render", "Views title changed by test_usetheme.test_subtheme_views_post_render");

    $this->assertRaw("test_basetheme_views_pre_render", "Views title changed by test_basetheme.test_basetheme_views_pre_render");
    $this->assertRaw("test_basetheme_views_post_render", "Views title changed by test_basetheme.test_basetheme_views_post_render");
  }

}
