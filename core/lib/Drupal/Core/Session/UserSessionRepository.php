<?php

declare(strict_types=1);

namespace Drupal\Core\Session;

use Drupal\Core\Database\Connection;

/**
 * Provides the default user session repository.
 */
readonly class UserSessionRepository implements UserSessionRepositoryInterface {

  public function __construct(protected Connection $connection) {
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(int $uid): void {
    try {
      // Delete session data.
      $this->connection->delete('sessions')
        ->condition('uid', $uid)
        ->execute();
    }
    // Swallow the error if the table hasn't been created yet.
    catch (\Exception) {
    }
  }

}
