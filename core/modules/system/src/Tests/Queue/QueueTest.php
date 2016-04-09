<?php

namespace Drupal\system\Tests\Queue;

use Drupal\Core\Database\Database;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\Memory;
use Drupal\simpletest\KernelTestBase;

/**
 * Queues and dequeues a set of items to check the basic queue functionality.
 *
 * @group Queue
 */
class QueueTest extends KernelTestBase {

  /**
   * Tests the System queue.
   */
  public function testSystemQueue() {
    // Create two queues.
    $queue1 = new DatabaseQueue($this->randomMachineName(), Database::getConnection());
    $queue1->createQueue();
    $queue2 = new DatabaseQueue($this->randomMachineName(), Database::getConnection());
    $queue2->createQueue();

    $this->queueTest($queue1, $queue2);
  }

  /**
   * Tests the Memory queue.
   */
  public function testMemoryQueue() {
    // Create two queues.
    $queue1 = new Memory($this->randomMachineName());
    $queue1->createQueue();
    $queue2 = new Memory($this->randomMachineName());
    $queue2->createQueue();

    $this->queueTest($queue1, $queue2);
  }

  /**
   * Queues and dequeues a set of items to check the basic queue functionality.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue1
   *   An instantiated queue object.
   * @param \Drupal\Core\Queue\QueueInterface $queue2
   *   An instantiated queue object.
   */
  protected function queueTest($queue1, $queue2) {
    // Create four items.
    $data = array();
    for ($i = 0; $i < 4; $i++) {
      $data[] = array($this->randomMachineName() => $this->randomMachineName());
    }

    // Queue items 1 and 2 in the queue1.
    $queue1->createItem($data[0]);
    $queue1->createItem($data[1]);

    // Retrieve two items from queue1.
    $items = array();
    $new_items = array();

    $items[] = $item = $queue1->claimItem();
    $new_items[] = $item->data;

    $items[] = $item = $queue1->claimItem();
    $new_items[] = $item->data;

    // First two dequeued items should match the first two items we queued.
    $this->assertEqual($this->queueScore($data, $new_items), 2, 'Two items matched');

    // Add two more items.
    $queue1->createItem($data[2]);
    $queue1->createItem($data[3]);

    $this->assertTrue($queue1->numberOfItems(), 'Queue 1 is not empty after adding items.');
    $this->assertFalse($queue2->numberOfItems(), 'Queue 2 is empty while Queue 1 has items');

    $items[] = $item = $queue1->claimItem();
    $new_items[] = $item->data;

    $items[] = $item = $queue1->claimItem();
    $new_items[] = $item->data;

    // All dequeued items should match the items we queued exactly once,
    // therefore the score must be exactly 4.
    $this->assertEqual($this->queueScore($data, $new_items), 4, 'Four items matched');

    // There should be no duplicate items.
    $this->assertEqual($this->queueScore($new_items, $new_items), 4, 'Four items matched');

    // Delete all items from queue1.
    foreach ($items as $item) {
      $queue1->deleteItem($item);
    }

    // Check that both queues are empty.
    $this->assertFalse($queue1->numberOfItems(), 'Queue 1 is empty');
    $this->assertFalse($queue2->numberOfItems(), 'Queue 2 is empty');
  }

  /**
   * Returns the number of equal items in two arrays.
   */
  protected function queueScore($items, $new_items) {
    $score = 0;
    foreach ($items as $item) {
      foreach ($new_items as $new_item) {
        if ($item === $new_item) {
          $score++;
        }
      }
    }
    return $score;
  }
}
