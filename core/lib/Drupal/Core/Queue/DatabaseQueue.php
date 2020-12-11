<?php

namespace Drupal\Core\Queue;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Default queue implementation.
 *
 * @ingroup queue
 */
class DatabaseQueue implements ReliableQueueInterface, QueueGarbageCollectionInterface, DelayableQueueInterface {

  use DependencySerializationTrait;

  /**
   * The database table name.
   */
  const TABLE_NAME = 'queue';

  /**
   * The name of the queue this instance is working with.
   *
   * @var string
   */
  protected $name;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a \Drupal\Core\Queue\DatabaseQueue object.
   *
   * @param string $name
   *   The name of the queue.
   * @param \Drupal\Core\Database\Connection $connection
   *   The Connection object containing the key-value tables.
   */
  public function __construct($name, Connection $connection) {
    $this->name = $name;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function createItem($data) {
    $try_again = FALSE;
    try {
      $id = $this->doCreateItem($data);
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if (!$try_again = $this->ensureTableExists()) {
        // If the exception happened for other reason than the missing table,
        // propagate the exception.
        throw $e;
      }
    }
    // Now that the table has been created, try again if necessary.
    if ($try_again) {
      $id = $this->doCreateItem($data);
    }
    return $id;
  }

  /**
   * Adds a queue item and store it directly to the queue.
   *
   * @param $data
   *   Arbitrary data to be associated with the new task in the queue.
   *
   * @return
   *   A unique ID if the item was successfully created and was (best effort)
   *   added to the queue, otherwise FALSE. We don't guarantee the item was
   *   committed to disk etc, but as far as we know, the item is now in the
   *   queue.
   */
  protected function doCreateItem($data) {
    $query = $this->connection->insert(static::TABLE_NAME)
      ->fields([
        'name' => $this->name,
        'data' => serialize($data),
        // We cannot rely on REQUEST_TIME because many items might be created
        // by a single request which takes longer than 1 second.
        'created' => \Drupal::time()->getCurrentTime(),
      ]);
    // Return the new serial ID, or FALSE on failure.
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function numberOfItems() {
    try {
      return (int) $this->connection->query('SELECT COUNT([item_id]) FROM {' . static::TABLE_NAME . '} WHERE [name] = :name', [':name' => $this->name])
        ->fetchField();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If there is no table there cannot be any items.
      return 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function claimItem($lease_time = 30) {
    // Claim an item by updating its expire fields. If claim is not successful
    // another thread may have claimed the item in the meantime. Therefore loop
    // until an item is successfully claimed or we are reasonably sure there
    // are no unclaimed items left.
    while (TRUE) {
      try {
        $item = $this->connection->queryRange('SELECT [data], [created], [item_id] FROM {' . static::TABLE_NAME . '} q WHERE [expire] = 0 AND [name] = :name ORDER BY [created], [item_id] ASC', 0, 1, [':name' => $this->name])->fetchObject();
      }
      catch (\Exception $e) {
        $this->catchException($e);
      }

      // If the table does not exist there are no items currently available to
      // claim.
      if (empty($item)) {
        return FALSE;
      }

      // Try to update the item. Only one thread can succeed in UPDATEing the
      // same row. We cannot rely on REQUEST_TIME because items might be
      // claimed by a single consumer which runs longer than 1 second. If we
      // continue to use REQUEST_TIME instead of the current time(), we steal
      // time from the lease, and will tend to reset items before the lease
      // should really expire.
      $update = $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => \Drupal::time()->getCurrentTime() + $lease_time,
        ])
        ->condition('item_id', $item->item_id)
        ->condition('expire', 0);
      // If there are affected rows, this update succeeded.
      if ($update->execute()) {
        $item->data = unserialize($item->data);
        return $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function releaseItem($item) {
    try {
      $update = $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => 0,
        ])
        ->condition('item_id', $item->item_id);
      return (bool) $update->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table doesn't exist we should consider the item released.
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delayItem($item, int $delay) {
    // Only allow a positive delay interval.
    if ($delay < 0) {
      throw new \InvalidArgumentException('$delay must be non-negative');
    }

    try {
      // Add the delay relative to the current time.
      $expire = \Drupal::time()->getCurrentTime() + $delay;
      // Update the expiry time of this item.
      $update = $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => $expire,
        ])
        ->condition('item_id', $item->item_id);
      return (bool) $update->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
      // If the table doesn't exist we should consider the item nonexistent.
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteItem($item) {
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('item_id', $item->item_id)
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue() {
    // All tasks are stored in a single database table (which is created on
    // demand) so there is nothing we need to do to create a new queue.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteQueue() {
    try {
      $this->connection->delete(static::TABLE_NAME)
        ->condition('name', $this->name)
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function garbageCollection() {
    try {
      // Clean up the queue for failed batches.
      $this->connection->delete(static::TABLE_NAME)
        ->condition('created', REQUEST_TIME - 864000, '<')
        ->condition('name', 'drupal_batch:%', 'LIKE')
        ->execute();

      // Reset expired items in the default queue implementation table. If that's
      // not used, this will simply be a no-op.
      $this->connection->update(static::TABLE_NAME)
        ->fields([
          'expire' => 0,
        ])
        ->condition('expire', 0, '<>')
        ->condition('expire', REQUEST_TIME, '<')
        ->execute();
    }
    catch (\Exception $e) {
      $this->catchException($e);
    }
  }

  /**
   * Check if the table exists and create it if not.
   */
  protected function ensureTableExists() {
    try {
      $database_schema = $this->connection->schema();
      if (!$database_schema->tableExists(static::TABLE_NAME)) {
        $schema_definition = $this->schemaDefinition();
        $database_schema->createTable(static::TABLE_NAME, $schema_definition);
        return TRUE;
      }
    }
    // If another process has already created the queue table, attempting to
    // recreate it will throw an exception. In this case just catch the
    // exception and do nothing.
    catch (DatabaseException $e) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Act on an exception when queue might be stale.
   *
   * If the table does not yet exist, that's fine, but if the table exists and
   * yet the query failed, then the queue is stale and the exception needs to
   * propagate.
   *
   * @param $e
   *   The exception.
   *
   * @throws \Exception
   *   If the table exists the exception passed in is rethrown.
   */
  protected function catchException(\Exception $e) {
    if ($this->connection->schema()->tableExists(static::TABLE_NAME)) {
      throw $e;
    }
  }

  /**
   * Defines the schema for the queue table.
   *
   * @internal
   */
  public function schemaDefinition() {
    return [
      'description' => 'Stores items in queues.',
      'fields' => [
        'item_id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary Key: Unique item ID.',
        ],
        'name' => [
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The queue name.',
        ],
        'data' => [
          'type' => 'blob',
          'not null' => FALSE,
          'size' => 'big',
          'serialize' => TRUE,
          'description' => 'The arbitrary data for the item.',
        ],
        'expire' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Timestamp when the claim lease expires on the item.',
        ],
        'created' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Timestamp when the item was created.',
        ],
      ],
      'primary key' => ['item_id'],
      'indexes' => [
        'name_created' => ['name', 'created'],
        'expire' => ['expire'],
      ],
    ];
  }

}
