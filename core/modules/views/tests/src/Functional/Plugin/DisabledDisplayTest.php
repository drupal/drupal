<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the ability to disable and enable view displays.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisabledDisplayTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_disabled_display'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['views_test_config']): void {
    parent::setUp($import_test_views, $modules);

    $this->enableViewsTestModule();

    $this->drupalPlaceBlock('page_title_block');

    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that views displays can be disabled.
   *
   * This method only checks the page displays via a HTTP request, but should
   * the .enabled property disappear from the schema both the load and save
   * calls will start failing.
   */
  public function testDisabledDisplays(): void {
    // The displays defined in this view.
    $display_ids = ['attachment_1', 'block_1', 'embed_1', 'feed_1', 'page_2'];

    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    // Load the test view and initialize its displays.
    $view = $this->container->get('entity_type.manager')->getStorage('view')->load('test_disabled_display');
    $view->getExecutable()->setDisplay();

    // Enabled page display should return content.
    $this->drupalGet('test-disabled-display');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'test_disabled_display');

    // Disabled page view should 404.
    $this->drupalGet('test-disabled-display-2');
    $this->assertSession()->statusCodeEquals(404);

    // Enable each disabled display and save the view.
    foreach ($display_ids as $display_id) {
      $view->getExecutable()->displayHandlers->get($display_id)->setOption('enabled', TRUE);
      $view->save();
      $enabled = $view->getExecutable()->displayHandlers->get($display_id)->isEnabled();
      $this->assertTrue($enabled, 'Display ' . $display_id . ' is now enabled');
    }

    \Drupal::service('router.builder')->rebuildIfNeeded();

    // Check that the originally disabled page_2 display is now enabled.
    $this->drupalGet('test-disabled-display-2');
    $this->assertSession()->elementTextEquals('xpath', '//h1[@class="page-title"]', 'test_disabled_display');

    // Disable each disabled display and save the view.
    foreach ($display_ids as $display_id) {
      $view->getExecutable()->displayHandlers->get($display_id)->setOption('enabled', FALSE);
      $view->save();
      $enabled = $view->getExecutable()->displayHandlers->get($display_id)->isEnabled();
      $this->assertFalse($enabled, 'Display ' . $display_id . ' is now disabled');
    }

    \Drupal::service('router.builder')->rebuild();

    // Check that the page_1 display still works.
    $this->drupalGet('test-disabled-display');
    $this->assertSession()->statusCodeEquals(200);

    // Check that the page_2 display is now disabled again.
    $this->drupalGet('test-disabled-display-2');
    $this->assertSession()->statusCodeEquals(404);
  }

}
