<?php

/**
 * @file
 * Contains \Drupal\content_translation\Tests\Views\ContentTranslationViewsUITest.
 */

namespace Drupal\content_translation\Tests\Views;

use Drupal\views_ui\Tests\UITestBase;

/**
 * Tests the views UI when content_translation is enabled.
 *
 * @group content_translation
 */
class ContentTranslationViewsUITest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('content_translation');

  /**
   * Tests the views UI.
   */
  public function testViewsUI() {
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertTitle(t('@label (@table) | @site-name', array('@label' => 'Test view', '@table' => 'Views test data', '@site-name' => $this->container->get('config.factory')->get('system.site')->get('name'))));
  }

}
