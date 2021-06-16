<?php

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Update as QueryUpdate;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Update.
 */
class Update extends QueryUpdate {

  public function execute() {
    $max_placeholder = 0;
    $blobs = [];
    $blob_count = 0;

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions);

    // Fetch the list of blobs and sequences used on that table.
    $table_information = $this->connection->schema()->queryTableInformation($this->table);

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        foreach ($data['arguments'] as $placeholder => $argument) {
          // We assume that an expression will never happen on a BLOB field,
          // which is a fairly safe assumption to make since in most cases
          // it would be an invalid query anyway.
          $stmt->getClientStatement()->bindParam($placeholder, $data['arguments'][$placeholder]);
        }
      }
      if ($data['expression'] instanceof SelectInterface) {
        $data['expression']->compile($this->connection, $this);
        $select_query_arguments = $data['expression']->arguments();
        foreach ($select_query_arguments as $placeholder => $argument) {
          $stmt->getClientStatement()->bindParam($placeholder, $select_query_arguments[$placeholder]);
        }
      }
      unset($fields[$field]);
    }

    foreach ($fields as $field => $value) {
      $placeholder = ':db_update_placeholder_' . ($max_placeholder++);

      if (isset($table_information->blob_fields[$field])) {
        $blobs[$blob_count] = fopen('php://memory', 'a');
        fwrite($blobs[$blob_count], $value);
        rewind($blobs[$blob_count]);
        $stmt->getClientStatement()->bindParam($placeholder, $blobs[$blob_count], \PDO::PARAM_LOB);
        ++$blob_count;
      }
      else {
        $stmt->getClientStatement()->bindParam($placeholder, $fields[$field]);
      }
    }

    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);

      $arguments = $this->condition->arguments();
      foreach ($arguments as $placeholder => $value) {
        $stmt->getClientStatement()->bindParam($placeholder, $arguments[$placeholder]);
      }
    }

    $options = $this->queryOptions;
    $options['already_prepared'] = TRUE;
    $options['return'] = Database::RETURN_AFFECTED;

    $this->connection->addSavepoint();
    try {
      $stmt->execute(NULL, $options);
      $this->connection->releaseSavepoint();
      $stmt->allowRowCount = TRUE;
      return $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      throw $e;
    }
  }

}
