<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\Memory.
 */

namespace Drupal\Core\Queue;

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
   * Constructs a Memory object.
   *
   * @param string $name
   *   An arbitrary string. The name of the queue to work with.
   */
  public function __construct($name) {
    $this->queue = array();
    $this->idSequence = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $item = new \stdClass();
    $item->item_id = $this->idSequence++;
    $item->data = $data;
    $item->created = time();
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
        $item->expire = time() + $lease_time;
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
    $this->queue = array();
    $this->idSequence = 0;
  }
}
