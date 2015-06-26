<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\pgsql\Delete.
 */

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Delete as QueryDelete;

class Delete extends QueryDelete {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->connection->addSavepoint();
    try {
      $result = parent::execute();
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      throw $e;
    }
    $this->connection->releaseSavepoint();

    return $result;
  }

}
