<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the Cron Queue runner.
 *
 * @group system
 */
class CronQueueTest extends KernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'cron_queue_test'];

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // These additional tables are necessary because $this->cron->run() calls
    // system_cron().
    $this->installSchema('system', ['key_value_expire']);

    $this->connection = Database::getConnection();
    $this->cron = \Drupal::service('cron');
  }

  /**
   * Tests that exceptions thrown by workers are handled properly.
   */
  public function testExceptions() {
    // Get the queue to test the normal Exception.
    $queue = $this->container->get('queue')->get('cron_queue_test_exception');

    // Enqueue an item for processing.
    $queue->createItem([$this->randomMachineName() => $this->randomMachineName()]);

    // Run cron; the worker for this queue should throw an exception and handle
    // it.
    $this->cron->run();
    $this->assertEqual(\Drupal::state()->get('cron_queue_test_exception'), 1);

    // The item should be left in the queue.
    $this->assertEqual($queue->numberOfItems(), 1, 'Failing item still in the queue after throwing an exception.');

    // Expire the queue item manually. system_cron() relies in REQUEST_TIME to
    // find queue items whose expire field needs to be reset to 0. This is a
    // Kernel test, so REQUEST_TIME won't change when cron runs.
    // @see system_cron()
    // @see \Drupal\Core\Cron::processQueues()
    $this->connection->update('queue')
      ->condition('name', 'cron_queue_test_exception')
      ->fields(['expire' => REQUEST_TIME - 1])
      ->execute();
    $this->cron->run();
    $this->assertEqual(\Drupal::state()->get('cron_queue_test_exception'), 2);
    $this->assertEqual($queue->numberOfItems(), 0, 'Item was processed and removed from the queue.');

    // Get the queue to test the specific SuspendQueueException.
    $queue = $this->container->get('queue')->get('cron_queue_test_broken_queue');

    // Enqueue several item for processing.
    $queue->createItem('process');
    $queue->createItem('crash');
    $queue->createItem('ignored');

    // Run cron; the worker for this queue should process as far as the crashing
    // item.
    $this->cron->run();

    // Only one item should have been processed.
    $this->assertEqual($queue->numberOfItems(), 2, 'Failing queue stopped processing at the failing item.');

    // Check the items remaining in the queue. The item that throws the
    // exception gets released by cron, so we can claim it again to check it.
    $item = $queue->claimItem();
    $this->assertEqual($item->data, 'crash', 'Failing item remains in the queue.');
    $item = $queue->claimItem();
    $this->assertEqual($item->data, 'ignored', 'Item beyond the failing item remains in the queue.');

    // Test the requeueing functionality.
    $queue = $this->container->get('queue')->get('cron_queue_test_requeue_exception');
    $queue->createItem([]);
    $this->cron->run();

    $this->assertEquals(2, \Drupal::state()->get('cron_queue_test_requeue_exception'));
    $this->assertEquals(0, $queue->numberOfItems());
  }

}
