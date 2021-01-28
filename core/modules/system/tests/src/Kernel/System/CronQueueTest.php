<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\Memory;
use Drupal\KernelTests\KernelTestBase;
use Drupal\cron_queue_test\Plugin\QueueWorker\CronQueueTestDatabaseDelayException;
use Prophecy\Argument;

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
   * The fake current time used for queue worker / cron testing purposes.
   *
   * This value should be greater than or equal to zero.
   *
   * @var int
   */
  protected $currentTime = 1000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = Database::getConnection();
    $this->cron = \Drupal::service('cron');

    $time = $this->prophesize('Drupal\Component\Datetime\TimeInterface');
    $time->getCurrentTime()->willReturn($this->currentTime);
    $time->getRequestTime()->willReturn($this->currentTime);
    \Drupal::getContainer()->set('datetime.time', $time->reveal());
    $this->assertEquals($this->currentTime, \Drupal::time()->getCurrentTime());
    $this->assertEquals($this->currentTime, \Drupal::time()->getRequestTime());

    $realQueueFactory = $this->container->get('queue');
    $queue_factory = $this->prophesize(get_class($realQueueFactory));
    $database = new DatabaseQueue('cron_queue_test_database_delay_exception', $this->connection);
    $memory = new Memory('cron_queue_test_memory_delay_exception');
    $queue_factory->get('cron_queue_test_database_delay_exception', Argument::cetera())->willReturn($database);
    $queue_factory->get('cron_queue_test_memory_delay_exception', Argument::cetera())->willReturn($memory);
    $queue_factory->get(Argument::any(), Argument::cetera())->will(function ($args) use ($realQueueFactory) {
      return $realQueueFactory->get($args[0], $args[1] ?? FALSE);
    });

    $this->container->set('queue', $queue_factory->reveal());
  }

  /**
   * Tests that DelayedRequeueException behaves as expected when running cron.
   */
  public function testDelayException() {
    $database = $this->container->get('queue')->get('cron_queue_test_database_delay_exception');
    $memory = $this->container->get('queue')->get('cron_queue_test_memory_delay_exception');

    // Ensure that the queues are of the correct type for this test.
    $this->assertInstanceOf('Drupal\Core\Queue\DelayableQueueInterface', $database);
    $this->assertNotInstanceOf('Drupal\Core\Queue\DelayableQueueInterface', $memory);

    // Get the queue worker plugin manager.
    $manager = $this->container->get('plugin.manager.queue_worker');
    $definitions = $manager->getDefinitions();
    $this->assertNotEmpty($database_lease_time = $definitions['cron_queue_test_database_delay_exception']['cron']['time']);
    $this->assertNotEmpty($memory_lease_time = $definitions['cron_queue_test_memory_delay_exception']['cron']['time']);

    // Create the necessary test data and run cron.
    $database->createItem('test');
    $memory->createItem('test');
    $this->cron->run();

    // Fetch the expiry time for the database queue.
    $query = $this->connection->select('queue');
    $query->condition('name', 'cron_queue_test_database_delay_exception');
    $query->addField('queue', 'expire');
    $query->range(0, 1);
    $expire = $query->execute()->fetchField();

    // Assert that the delay interval is greater than the lease interval. This
    // allows us to assume that (if updated) the new expiry time will be greater
    // than the initial expiry time. We can then also assume that the new expiry
    // time offset will be identical to the delay interval.
    $this->assertGreaterThan($database_lease_time, CronQueueTestDatabaseDelayException::DELAY_INTERVAL);
    $this->assertGreaterThan($this->currentTime + $database_lease_time, $expire);
    $this->assertEquals(CronQueueTestDatabaseDelayException::DELAY_INTERVAL, $expire - $this->currentTime);

    // Ensure that the memory queue expiry time is unchanged after the
    // DelayedRequeueException has been thrown.
    $property = (new \ReflectionClass($memory))->getProperty('queue');
    $property->setAccessible(TRUE);
    $memory_queue_internal = $property->getValue($memory);
    $this->assertEquals($this->currentTime + $memory_lease_time, reset($memory_queue_internal)->expire);
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
    $this->assertEqual(1, \Drupal::state()->get('cron_queue_test_exception'));

    // The item should be left in the queue.
    $this->assertEqual(1, $queue->numberOfItems(), 'Failing item still in the queue after throwing an exception.');

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
    $this->assertEqual(2, \Drupal::state()->get('cron_queue_test_exception'));
    $this->assertEqual(0, $queue->numberOfItems(), 'Item was processed and removed from the queue.');

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
    $this->assertEqual(2, $queue->numberOfItems(), 'Failing queue stopped processing at the failing item.');

    // Check the items remaining in the queue. The item that throws the
    // exception gets released by cron, so we can claim it again to check it.
    $item = $queue->claimItem();
    $this->assertEqual('crash', $item->data, 'Failing item remains in the queue.');
    $item = $queue->claimItem();
    $this->assertEqual('ignored', $item->data, 'Item beyond the failing item remains in the queue.');

    // Test the requeueing functionality.
    $queue = $this->container->get('queue')->get('cron_queue_test_requeue_exception');
    $queue->createItem([]);
    $this->cron->run();

    $this->assertEquals(2, \Drupal::state()->get('cron_queue_test_requeue_exception'));
    $this->assertEquals(0, $queue->numberOfItems());
  }

  /**
   * Tests that database queue implementation complies with interfaces specs.
   */
  public function testDatabaseQueueReturnTypes(): void {
    /** @var \Drupal\Core\Queue\DatabaseQueue $queue */
    $queue = $this->container
      ->get('queue')
      ->get('cron_queue_test_database_delay_exception');
    static::assertInstanceOf(DatabaseQueue::class, $queue);

    $queue->createItem(12);
    $item = $queue->claimItem();
    static::assertTrue($queue->delayItem($item, 1));
    static::assertTrue($queue->releaseItem($item));
    $queue->deleteItem($item);
    static::assertFalse($queue->delayItem($item, 1));
    static::assertFalse($queue->releaseItem($item));
  }

}
