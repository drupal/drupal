<?php

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Delete as QueryDelete;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Delete.
 */
class Delete extends QueryDelete {

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection, $table, array $options = []) {
    // @todo Remove the __construct in D10.
    // @see https://www.drupal.org/project/drupal/issues/3210310
    parent::__construct($connection, $table, $options);
    unset($options['return']);
  }

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
