<?php

/**
 * @file
 * Contains \Drupal\Core\Batch\BatchStorage.
 */

namespace Drupal\Core\Batch;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Access\CsrfTokenGenerator;

class BatchStorage implements BatchStorageInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

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
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  public function __construct(Connection $connection, SessionInterface $session, CsrfTokenGenerator $csrf_token) {
    $this->connection = $connection;
    $this->session = $session;
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    // Ensure that a session is started before using the CSRF token generator.
    $this->session->start();
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
    $this->session->start();
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
