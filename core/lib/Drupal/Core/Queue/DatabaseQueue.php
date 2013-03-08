<?php

/**
 * @file
 * Contains \Drupal\Core\Queue\DatabaseQueue.
 */

namespace Drupal\Core\Queue;

use Drupal\Core\Database\Connection;

/**
 * Default queue implementation.
 */
class DatabaseQueue implements ReliableQueueInterface {

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection $connection
   */
  protected $connection;

  /**
   * Constructs this factory object.
   *
   * @param string $name
   *   The name of the queue.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  function __construct($name, Connection $connection) {
    $this->name = $name;
    $this->connection = $connection;
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createItem().
   */
  public function createItem($data) {
    $query = $this->connection->insert('queue')
      ->fields(array(
        'name' => $this->name,
        'data' => serialize($data),
        // We cannot rely on REQUEST_TIME because many items might be created
        // by a single request which takes longer than 1 second.
        'created' => time(),
      ));
    // Return the new serial ID, or FALSE on failure.
    return $query->execute();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::numberOfItems().
   */
  public function numberOfItems() {
    return $this->connection->query('SELECT COUNT(item_id) FROM {queue} WHERE name = :name', array(':name' => $this->name))->fetchField();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::claimItem().
   */
  public function claimItem($lease_time = 30) {
    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      $item = $this->connection->queryRange('SELECT data, created, item_id FROM {queue} q WHERE expire = 0 AND name = :name ORDER BY created ASC', 0, 1, array(':name' => $this->name))->fetchObject();
      if ($item) {
        // Try to update the item. Only one thread can succeed in UPDATEing the
        // same row. We cannot rely on REQUEST_TIME because items might be
        // claimed by a single consumer which runs longer than 1 second. If we
        // continue to use REQUEST_TIME instead of the current time(), we steal
        // time from the lease, and will tend to reset items before the lease
        // should really expire.
        $update = $this->connection->update('queue')
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

  /**
   * Implements Drupal\Core\Queue\QueueInterface::releaseItem().
   */
  public function releaseItem($item) {
    $update = $this->connection->update('queue')
      ->fields(array(
        'expire' => 0,
      ))
      ->condition('item_id', $item->item_id);
      return $update->execute();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteItem().
   */
  public function deleteItem($item) {
    $this->connection->delete('queue')
      ->condition('item_id', $item->item_id)
      ->execute();
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::createQueue().
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created when
    // Drupal is first installed) so there is nothing we need to do to create
    // a new queue.
  }

  /**
   * Implements Drupal\Core\Queue\QueueInterface::deleteQueue().
   */
  public function deleteQueue() {
    $this->connection->delete('queue')
      ->condition('name', $this->name)
      ->execute();
  }
}
