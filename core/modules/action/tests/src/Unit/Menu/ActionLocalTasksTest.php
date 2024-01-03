<?php

declare(strict_types=1);

namespace Drupal\Tests\action\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests action local tasks.
 *
 * @group action
 */
class ActionLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = ['action' => 'core/modules/action'];
    parent::setUp();
  }

  /**
   * Tests local task existence.
   */
  public function testActionLocalTasks() {
    $this->assertLocalTasks('entity.action.collection', [['action.admin']]);
  }

}
