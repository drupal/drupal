<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

/**
 * SQLite-specific implementation of a PDO connection.
 *
 * SQLite does not implement row locks, so when it acquires a lock, it locks
 * the entire database. To improve performance, by default SQLite tries to
 * defer acquiring a write lock until the first write operation of a
 * transaction rather than when the transaction is started. Unfortunately, this
 * seems to be incompatible with how Drupal uses transactions, and frequently
 * leads to deadlocks.
 *
 * Therefore, this class overrides \PDO to begin transactions with a
 * BEGIN IMMEDIATE TRANSACTION statement, for which SQLite acquires the write
 * lock immediately. This can incur some performance cost in a high concurrency
 * environment: it adds approximately 5% to the time it takes to execute Drupal
 * core's entire test suite on DrupalCI, and it potentially could add more in a
 * higher concurrency environment. However, under high enough concurrency of a
 * Drupal application, SQLite isn't the best choice anyway, and a database
 * engine that implements row locking, such as MySQL or PostgreSQL, is more
 * suitable.
 *
 * Because of https://bugs.php.net/42766 we have to create such a transaction
 * manually which means we must also override commit() and rollback().
 *
 * @see https://www.drupal.org/project/drupal/issues/1120020
 */
class PDOConnection extends \PDO {

  /**
   * {@inheritdoc}
   */
  public function beginTransaction(): bool {
    return $this->exec('BEGIN IMMEDIATE TRANSACTION') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function commit(): bool {
    return $this->exec('COMMIT') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rollBack(): bool {
    return $this->exec('ROLLBACK') !== FALSE;
  }

}
