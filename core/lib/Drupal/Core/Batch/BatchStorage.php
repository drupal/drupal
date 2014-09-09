<?php

/**
 * @file
 * Contains \Drupal\Core\Batch\BatchStorage.
 */

namespace Drupal\Core\Batch;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\SessionManager;
use Drupal\Core\Access\CsrfTokenGenerator;

class BatchStorage implements BatchStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManager
   */
  protected $sessionManager;

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $csrfToken;

  /**
   * Constructs the database batch storage service.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Session\SessionManager $session_manager
   *   The session manager.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(Connection $connection, SessionManager $session_manager, CsrfTokenGenerator $csrf_token) {
    $this->connection = $connection;
    $this->sessionManager = $session_manager;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // Ensure that a session is started before using the CSRF token generator.
    $this->sessionManager->start();
    $batch = $this->connection->query("SELECT batch FROM {batch} WHERE bid = :bid AND token = :token", array(
      ':bid' => $id,
      ':token' => $this->csrfToken->get($id),
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
  public function create(array $batch) {
    // Ensure that a session is started before using the CSRF token generator.
    $this->sessionManager->start();
    $this->connection->insert('batch')
      ->fields(array(
        'bid' => $batch['id'],
        'timestamp' => REQUEST_TIME,
        'token' => $this->csrfToken->get($batch['id']),
        'batch' => serialize($batch),
      ))
      ->execute();
  }

}
