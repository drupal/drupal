<?php

namespace Drupal\Core\Queue;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;

/**
 * Static queue implementation.
 *
 * This allows "undelayed" variants of processes relying on the Queue
 * interface. The queue data resides in memory. It should only be used for
 * items that will be queued and dequeued within a given page request.
 *
 * @ingroup queue
 */
class Memory implements QueueInterface {

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
  protected $idSequence;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a Memory object.
   *
   * @param string $name
   *   An arbitrary string. The name of the queue to work with.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct($name, Connection $connection = NULL, TimeInterface $time = NULL) {
    $this->queue = [];
    $this->idSequence = 0;

    if (!$time) {
      @trigger_error('The time service must be passed to ' . __NAMESPACE__ . '\Memory::__construct(). It was added in drupal:9.3.0 and will be required before drupal:10.0.0. See https://www.drupal.org/node/3161659', E_USER_DEPRECATED);
      $time = \Drupal::time();
    }
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $item = new \stdClass();
    $item->item_id = $this->idSequence++;
    $item->data = $data;
    $item->created = $this->time->getCurrentTime();
    $item->expire = 0;
    $this->queue[$item->item_id] = $item;
    return $item->item_id;
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    return count($this->queue);
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 30) {
    foreach ($this->queue as $key => $item) {
      if ($item->expire == 0) {
        $item->expire = $this->time->getCurrentTime() + $lease_time;
        $this->queue[$key] = $item;
        return $item;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    unset($this->queue[$item->item_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    if (isset($this->queue[$item->item_id]) && $this->queue[$item->item_id]->expire != 0) {
      $this->queue[$item->item_id]->expire = 0;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // Nothing needed here.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    $this->queue = [];
    $this->idSequence = 0;
  }

}
