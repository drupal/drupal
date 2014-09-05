<?php

/**
 * @file
 * Contains \Drupal\Tests\action\Unit\Menu\ActionLocalTasksTest.
 */

namespace Drupal\Tests\action\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests action local tasks.
 *
 * @group action
 */
class ActionLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
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
