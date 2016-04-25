<?php

namespace Drupal\views\Tests\Plugin;

/**
 * Tests the ability to disable and enable view displays.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\display\Feed
 */
class DisabledDisplayTest extends PluginTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_disabled_display');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'node', 'views');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->drupalPlaceBlock('page_title_block');

    $admin_user = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that views displays can be disabled.
   *
   * This method only checks the page displays via a HTTP request, but should
   * the .enabled property disappear from the schema both the load and save
   * calls will start failing.
   */
  public function testDisabledDisplays() {
    // The displays defined in this view.
    $display_ids = array('attachment_1', 'block_1', 'embed_1', 'feed_1', 'page_2');

    $this->drupalCreateContentType(['type' => 'page']);
    $this->drupalCreateNode();

    // Load the test view and initialize its displays.
    $view = $this->container->get('entity.manager')->getStorage('view')->load('test_disabled_display');
    $view->getExecutable()->setDisplay();

    // Enabled page display should return content.
    $this->drupalGet('test-disabled-display');
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual($result[0], 'test_disabled_display', 'The enabled page_1 display is accessible.');

    // Disabled page view should 404.
    $this->drupalGet('test-disabled-display-2');
    $this->assertResponse(404);

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
    $result = $this->xpath('//h1[@class="page-title"]');
    $this->assertEqual($result[0], 'test_disabled_display', 'The enabled page_2 display is accessible.');

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
    $this->assertResponse(200);

    // Check that the page_2 display is now disabled again.
    $this->drupalGet('test-disabled-display-2');
    $this->assertResponse(404);
  }

}
