<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Handler\FieldDropButtonTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewTestData;

/**
 * Tests the dropbutton field handler.
 *
 * @group views
 * @see \Drupal\system\Plugin\views\field\Dropbutton
 */
class FieldDropButtonTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_dropbutton');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Tests dropbutton field.
   */
  public function testDropbutton() {
    // Create some test nodes.
    $nodes = array();
    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode();
    }

    $this->drupalGet('test-dropbutton');
    foreach ($nodes as $node) {
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => '/node/' . $node->id(), ':title' => $node->label()));
      $this->assertEqual(count($result), 1, 'Just one node title link was found.');
      $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => '/node/' . $node->id(), ':title' => t('Custom Text')));
      $this->assertEqual(count($result), 1, 'Just one custom link was found.');
    }
  }

}
