<?php

/**
 * @file
 * Contains \Drupal\action\Tests\Menu\ActionLocalTasksTest.
 */

namespace Drupal\action\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of action local tasks.
 *
 * @group Drupal
 * @group Action
 */
class ActionLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Action local tasks test',
      'description' => 'Test action local tasks.',
      'group' => 'Action',
    );
  }

  public function setUp() {
    $this->directoryList = array('action' => 'core/modules/action');
    parent::setUp();
  }

  /**
   * Tests local task existence.
   */
  public function testActionLocalTasks() {
    $this->assertLocalTasks('action.admin', array(array('action.admin')));
  }

}
