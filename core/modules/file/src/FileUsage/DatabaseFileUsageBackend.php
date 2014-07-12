<?php

/**
 * @file
 * Definition of Drupal\file\FileUsage\DatabaseFileUsageBackend.
 */

namespace Drupal\file\FileUsage;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\file\FileInterface;

/**
 * Defines the database file usage backend. This is the default Drupal backend.
 */
class DatabaseFileUsageBackend extends FileUsageBase {

  /**
   * The database connection used to store file usage information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table used to store file usage information.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Construct the DatabaseFileUsageBackend.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the file usage
   *   information.
   * @param string $table
   *   (optional) The table to store file usage info. Defaults to 'file_usage'.
   */
  public function __construct(Connection $connection, $table = 'file_usage') {
    $this->connection = $connection;

    $this->tableName = $table;
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::add().
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
    $this->connection->merge($this->tableName)
      ->keys(array(
        'fid' => $file->id(),
        'module' => $module,
        'type' => $type,
        'id' => $id,
      ))
      ->fields(array('count' => $count))
      ->expression('count', 'count + :count', array(':count' => $count))
      ->execute();

    parent::add($file, $module, $type, $id, $count);
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::delete().
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
    // Delete rows that have a exact or less value to prevent empty rows.
    $query = $this->connection->delete($this->tableName)
      ->condition('module', $module)
      ->condition('fid', $file->id());
    if ($type && $id) {
      $query
        ->condition('type', $type)
        ->condition('id', $id);
    }
    if ($count) {
      $query->condition('count', $count, '<=');
    }
    $result = $query->execute();

    // If the row has more than the specified count decrement it by that number.
    if (!$result && $count > 0) {
      $query = $this->connection->update($this->tableName)
        ->condition('module', $module)
        ->condition('fid', $file->id());
      if ($type && $id) {
        $query
          ->condition('type', $type)
          ->condition('id', $id);
      }
      $query->expression('count', 'count - :count', array(':count' => $count));
      $query->execute();
    }

    parent::delete($file, $module, $type, $id, $count);
  }

  /**
   * Implements Drupal\file\FileUsage\FileUsageInterface::listUsage().
   */
  public function listUsage(FileInterface $file) {
    $result = $this->connection->select($this->tableName, 'f')
      ->fields('f', array('module', 'type', 'id', 'count'))
      ->condition('fid', $file->id())
      ->condition('count', 0, '>')
      ->execute();
    $references = array();
    foreach ($result as $usage) {
      $references[$usage->module][$usage->type][$usage->id] = $usage->count;
    }
    return $references;
  }
}
