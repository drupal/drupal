<?php

/**
 * @file
 * Contains \Drupal\action\Tests\Menu\ActionLocalTasksTest.
 */

namespace Drupal\action\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests action local tasks.
 *
 * @group action
 */
class ActionLocalTasksTest extends LocalTaskIntegrationTest {

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
