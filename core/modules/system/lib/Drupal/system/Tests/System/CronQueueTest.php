<?php

/**
 * @file
 * Contains Drupal\system\Tests\System\CronQueueTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\Memory;
use Drupal\simpletest\DrupalUnitTestBase;
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

    $queue = $this->container->get('queue')->get('cron_queue_test_exception');

    // Enqueue an item for processing.
    $queue->createItem(array($this->randomName() => $this->randomName()));

    // Run cron; the worker for this queue should throw an exception and handle
    // it.
    $this->cronRun();

    // The item should be left in the queue.
    $this->assertEqual($queue->numberOfItems(), 1, 'Failing item still in the queue after throwing an exception.');
  }
}
