<?php

/**
 * @file
 * Contains \Drupal\translation_entity\Tests\Views\TranslationEntityViewsUITest.
 */

namespace Drupal\translation_entity\Tests\Views;

use Drupal\views_ui\Tests\UITestBase;

/**
 * Tests the views UI when translation_entity is enabled.
 */
class TranslationEntityViewsUITest extends UITestBase {

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
  public static $modules = array('translation_entity');

  public static function getInfo() {
    return array(
      'name' => 'Entity Translation: Views UI',
      'description' => 'Tests the views UI when entity translation is enabled.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the views UI.
   */
  public function testViewsUI() {
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertTitle(t('@label (@table) | @site-name', array('@label' => 'Test view', '@table' => 'Views test data', '@site-name' => $this->container->get('config.factory')->get('system.site')->get('name'))));
  }

}
