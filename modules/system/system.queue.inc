<?php

/**
 * @file
 * Queue functionality.
 */

/**
 * @defgroup queue Queue operations
 * @{
 * Queue items to allow later processing.
 *
 * The queue system allows placing items in a queue and processing them later.
 * The system tries to ensure that only one consumer can process an item.
 *
 * Before a queue can be used it needs to be created by
 * DrupalQueueInterface::createQueue().
 *
 * Items can be added to the queue by passing an arbitrary data object to
 * DrupalQueueInterface::createItem().
 *
 * To process an item, call DrupalQueueInterface::claimItem() and specify how
 * long you want to have a lease for working on that item. When finished
 * processing, the item needs to be deleted by calling
 * DrupalQueueInterface::deleteItem(). If the consumer dies, the item will be
 * made available again by the DrupalQueueInterface implementation once the
 * lease expires. Another consumer will then be able to receive it when calling
 * DrupalQueueInterface::claimItem(). Due to this, the processing code should
 * be aware that an item might be handed over for processing more than once.
 *
 * The $item object used by the DrupalQueueInterface can contain arbitrary
 * metadata depending on the implementation. Systems using the interface should
 * only rely on the data property which will contain the information passed to
 * DrupalQueueInterface::createItem(). The full queue item returned by
 * DrupalQueueInterface::claimItem() needs to be passed to
 * DrupalQueueInterface::deleteItem() once processing is completed.
 *
 * There are two kinds of queue backends available: reliable, which preserves
 * the order of messages and guarantees that every item will be executed at
 * least once. The non-reliable kind only does a best effort to preserve order
 * in messages and to execute them at least once but there is a small chance
 * that some items get lost. For example, some distributed back-ends like
 * Amazon SQS will be managing jobs for a large set of producers and consumers
 * where a strict FIFO ordering will likely not be preserved. Another example
 * would be an in-memory queue backend which might lose items if it crashes.
 * However, such a backend would be able to deal with significantly more writes
 * than a reliable queue and for many tasks this is more important. See
 * aggregator_cron() for an example of how to effectively utilize a
 * non-reliable queue. Another example is doing Twitter statistics -- the small
 * possibility of losing a few items is insignificant next to power of the
 * queue being able to keep up with writes. As described in the processing
 * section, regardless of the queue being reliable or not, the processing code
 * should be aware that an item might be handed over for processing more than
 * once (because the processing code might time out before it finishes).
 */

/**
 * Factory class for interacting with queues.
 */
class DrupalQueue {
  /**
   * Returns the queue object for a given name.
   *
   * The following variables can be set by variable_set or $conf overrides:
   * - queue_class_$name: the class to be used for the queue $name.
   * - queue_default_class: the class to use when queue_class_$name is not
   *   defined. Defaults to SystemQueue, a reliable backend using SQL.
   * - queue_default_reliable_class: the class to use when queue_class_$name is
   *   not defined and the queue_default_class is not reliable. Defaults to
   *   SystemQueue.
   *
   * @param $name
   *   Arbitrary string. The name of the queue to work with.
   * @param $reliable
   *   TRUE if the ordering of items and guaranteeing every item executes at
   *   least once is important, FALSE if scalability is the main concern.
   *
   * @return
   *   The queue object for a given name.
   */
  public static function get($name, $reliable = FALSE) {
    static $queues;
    if (!isset($queues[$name])) {
      $class = variable_get('queue_class_' . $name, NULL);
      if (!$class) {
        $class = variable_get('queue_default_class', 'SystemQueue');
      }
      $object = new $class($name);
      if ($reliable && !$object instanceof DrupalReliableQueueInterface) {
        $class = variable_get('queue_default_reliable_class', 'SystemQueue');
        $object = new $class($name);
      }
      $queues[$name] = $object;
    }
    return $queues[$name];
  }
}

interface DrupalQueueInterface {
  /**
   * Start working with a queue.
   *
   * @param $name
   *   Arbitrary string. The name of the queue to work with.
   */
  public function __construct($name);

  /**
   * Add a queue item and store it directly to the queue.
   *
   * @param $data
   *   Arbitrary data to be associated with the new task in the queue.
   * @return
   *   TRUE if the item was successfully created and was (best effort) added
   *   to the queue, otherwise FALSE. We don't guarantee the item was
   *   committed to disk etc, but as far as we know, the item is now in the
   *   queue.
   */
  public function createItem($data);

  /**
   * Retrieve the number of items in the queue.
   *
   * This is intended to provide a "best guess" count of the number of items in
   * the queue. Depending on the implementation and the setup, the accuracy of
   * the results of this function may vary.
   *
   * e.g. On a busy system with a large number of consumers and items, the
   * result might only be valid for a fraction of a second and not provide an
   * accurate representation.
   *
   * @return
   *   An integer estimate of the number of items in the queue.
   */
  public function numberOfItems();

  /**
   * Claim an item in the queue for processing.
   *
   * @param $lease_time
   *   How long the processing is expected to take in seconds, defaults to an
   *   hour. After this lease expires, the item will be reset and another
   *   consumer can claim the item. For idempotent tasks (which can be run
   *   multiple times without side effects), shorter lease times would result
   *   in lower latency in case a consumer fails. For tasks that should not be
   *   run more than once (non-idempotent), a larger lease time will make it
   *   more rare for a given task to run multiple times in cases of failure,
   *   at the cost of higher latency.
   * @return
   *   On success we return an item object. If the queue is unable to claim an
   *   item it returns false. This implies a best effort to retrieve an item
   *   and either the queue is empty or there is some other non-recoverable
   *   problem.
   */
  public function claimItem($lease_time = 3600);

  /**
   * Delete a finished item from the queue.
   *
   * @param $item
   *   The item returned by DrupalQueueInterface::claimItem().
   */
  public function deleteItem($item);

  /**
   * Release an item that the worker could not process, so another
   * worker can come in and process it before the timeout expires.
   *
   * @param $item
   * @return boolean
   */
  public function releaseItem($item);

  /**
   * Create a queue.
   *
   * Called during installation and should be used to perform any necessary
   * initialization operations. This should not be confused with the
   * constructor for these objects, which is called every time an object is
   * instantiated to operate on a queue. This operation is only needed the
   * first time a given queue is going to be initialized (for example, to make
   * a new database table or directory to hold tasks for the queue -- it
   * depends on the queue implementation if this is necessary at all).
   */
  public function createQueue();

  /**
   * Delete a queue and every item in the queue.
   */
  public function deleteQueue();
}

/**
 * Reliable queue interface.
 *
 * Classes implementing this interface preserve the order of messages and
 * guarantee that every item will be executed at least once.
 */
interface DrupalReliableQueueInterface extends DrupalQueueInterface {
}

/**
 * Default queue implementation.
 */
class SystemQueue implements DrupalReliableQueueInterface {
  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  public function __construct($name) {
    $this->name = $name;
  }

  public function createItem($data) {
    // During a Drupal 6.x to 7.x update, drupal_get_schema() does not contain
    // the queue table yet, so we cannot rely on drupal_write_record().
    $query = db_insert('queue')
      ->fields(array(
        'name' => $this->name,
        'data' => serialize($data),
        // We cannot rely on REQUEST_TIME because many items might be created
        // by a single request which takes longer than 1 second.
        'created' => time(),
      ));
    return (bool) $query->execute();
  }

  public function numberOfItems() {
    return db_query('SELECT COUNT(item_id) FROM {queue} WHERE name = :name', array(':name' => $this->name))->fetchField();
  }

  public function claimItem($lease_time = 30) {
    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      $item = db_query_range('SELECT data, item_id FROM {queue} q WHERE expire = 0 AND name = :name ORDER BY created ASC', 0, 1, array(':name' => $this->name))->fetchObject();
      if ($item) {
        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = db_update('queue')
          ->fields(array(
            'expire' => time() + $lease_time,
          ))
          ->condition('item_id', $item->item_id)
          ->condition('expire', 0);
        // If there are affected rows, this update succeeded.
        if ($update->execute()) {
          $item->data = unserialize($item->data);
          return $item;
        }
      }
      else {
        // No items currently available to claim.
        return FALSE;
      }
    }
  }

  public function releaseItem($item) {
    $update = db_update('queue')
      ->fields(array(
        'expire' => 0,
      ))
      ->condition('item_id', $item->item_id);
      return $update->execute();
  }

  public function deleteItem($item) {
    db_delete('queue')
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // Drupal is first installed) so there is nothing we need to do to create
    // a new queue.
  }

  public function deleteQueue() {
    db_delete('queue')
      ->condition('name', $this->name)
      ->execute();
  }
}

/**
 * Static queue implementation.
 *
 * This allows "undelayed" variants of processes relying on the Queue
 * interface. The queue data resides in memory. It should only be used for
 * items that will be queued and dequeued within a given page request.
 */
class MemoryQueue implements DrupalQueueInterface {
  /**
   * The queue data.
   *
   * @var array
   */
  protected $queue;

  /**
   * Counter for item ids.
   *
   * @var int
   */
  protected $id_sequence;

  public function __construct($name) {
    $this->queue = array();
    $this->id_sequence = 0;
  }

  public function createItem($data) {
    $item = new stdClass();
    $item->item_id = $this->id_sequence++;
    $item->data = $data;
    $item->created = time();
    $item->expire = 0;
    $this->queue[$item->item_id] = $item;
  }

  public function numberOfItems() {
    return count($this->queue);
  }

  public function claimItem($lease_time = 30) {
    foreach ($this->queue as $key => $item) {
      if ($item->expire == 0) {
        $item->expire = time() + $lease_time;
        $this->queue[$key] = $item;
        return $item;
      }
    }
    return FALSE;
  }

  public function deleteItem($item) {
    unset($this->queue[$item->item_id]);
  }

  public function releaseItem($item) {
    if (isset($this->queue[$item->item_id]) && $this->queue[$item->item_id]->expire != 0) {
      $this->queue[$item->item_id]->expire = 0;
      return TRUE;
    }
    return FALSE;
  }

  public function createQueue() {
    // Nothing needed here.
  }

  public function deleteQueue() {
    $this->queue = array();
    $this->id_sequence = 0;
  }
}

/**
 * @} End of "defgroup queue".
 */
