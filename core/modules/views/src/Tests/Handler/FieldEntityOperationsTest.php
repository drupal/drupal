<?php

/**
 * @file
 * Definition of \Drupal\views\Tests\Handler\FieldEntityOperationsTest.
 */

namespace Drupal\views\Tests\Handler;

use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Views;

/**
 * Tests the core Drupal\views\Plugin\views\field\EntityOperations handler.
 *
 * @group views
 */
class FieldEntityOperationsTest extends HandlerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_operations');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Tests entity operations field.
   */
  public function testEntityOperations() {
    // Create some test nodes.
    $nodes = array();
    for ($i = 0; $i < 5; $i++) {
      $nodes[] = $this->drupalCreateNode();
    }

    $admin_user = $this->drupalCreateUser(array('access administration pages', 'bypass node access'));
    $this->drupalLogin($admin_user);
    $this->drupalGet('test-entity-operations');

    /* @var $node \Drupal\node\NodeInterface */
    foreach ($nodes as $node) {
      $operations = \Drupal::entityManager()->getListBuilder('node')->getOperations($node);
      foreach ($operations as $operation) {
        $result = $this->xpath('//ul[contains(@class, dropbutton)]/li/a[contains(@href, :path) and text()=:title]', array(':path' => $operation['url']->getInternalPath(), ':title' => $operation['title']));
        $this->assertEqual(count($result), 1, t('Found node @operation link.', array('@operation' => $operation['title'])));
      }
    }
  }

}
