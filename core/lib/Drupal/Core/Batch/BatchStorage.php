<?php

/**
 * @file
 * Contains \Drupal\Core\Batch\BatchStorage.
 */

namespace Drupal\Core\Batch;

use Drupal\Core\Database\Connection;

class BatchStorage implements BatchStorageInterface {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    $batch = $this->connection->query("SELECT batch FROM {batch} WHERE bid = :bid AND token = :token", array(
      ':bid' => $id,
      ':token' => drupal_get_token($id),
    ))->fetchField();
    if ($batch) {
      return unserialize($batch);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    $this->connection->delete('batch')
      ->condition('bid', $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update(array $batch) {
    $this->connection->update('batch')
      ->fields(array('batch' => serialize($batch)))
      ->condition('bid', $batch['id'])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup() {
    // Cleanup the batch table and the queue for failed batches.
    $this->connection->delete('batch')
      ->condition('timestamp', REQUEST_TIME - 864000, '<')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  function create(array $batch) {
    $this->connection->insert('batch')
      ->fields(array(
        'bid' => $batch['id'],
        'timestamp' => REQUEST_TIME,
        'token' => drupal_get_token($batch['id']),
        'batch' => serialize($batch),
      ))
      ->execute();
  }
}
