<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\CronQueueTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the handling of exceptions thrown by queue workers.
 */
class CronQueueTest extends WebTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = array('cron_queue_test');

  public static function getInfo() {
    return array(
      'name' => 'Cron Queue functionality',
      'description' => 'Tests the Cron Queue runner.',
      'group' => 'Queue',
    );
  }

  /**
   * Tests that exceptions thrown by workers are handled properly.
   */
  public function testExceptions() {
    // Get the queue to test the normal Exception.
    $queue = $this->container->get('queue')->get('cron_queue_test_exception');

    // Enqueue an item for processing.
    $queue->createItem(array($this->randomName() => $this->randomName()));

    // Run cron; the worker for this queue should throw an exception and handle
    // it.
    $this->cronRun();

    // The item should be left in the queue.
    $this->assertEqual($queue->numberOfItems(), 1, 'Failing item still in the queue after throwing an exception.');

    // Get the queue to test the specific SuspendQueueException.
    $queue = $this->container->get('queue')->get('cron_queue_test_broken_queue');

    // Enqueue several item for processing.
    $queue->createItem('process');
    $queue->createItem('crash');
    $queue->createItem('ignored');

    // Run cron; the worker for this queue should process as far as the crashing
    // item.
    $this->cronRun();

    // Only one item should have been processed.
    $this->assertEqual($queue->numberOfItems(), 2, 'Failing queue stopped processing at the failing item.');

    // Check the items remaining in the queue. The item that throws the
    // exception gets released by cron, so we can claim it again to check it.
    $item = $queue->claimItem();
    $this->assertEqual($item->data, 'crash', 'Failing item remains in the queue.');
    $item = $queue->claimItem();
    $this->assertEqual($item->data, 'ignored', 'Item beyond the failing item remains in the queue.');
  }
}
