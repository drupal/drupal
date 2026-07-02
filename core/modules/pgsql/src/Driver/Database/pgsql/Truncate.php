<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\Query\Truncate as QueryTruncate;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Truncate.
 */
class Truncate extends QueryTruncate {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    if ($this->connection->inTransaction()) {
      $savepoint = $this->connection->startTransaction('mimic_implicit_commit');
    }
    try {
      parent::execute();
    }
    catch (\Exception $e) {
      if (isset($savepoint)) {
        $savepoint->rollback();
      }
      throw $e;
    }
    if (isset($savepoint)) {
      $savepoint->commitOrRelease();
    }
  }

}
