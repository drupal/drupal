<?php

namespace Drupal\file\FileUsage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the file usage
   *   information.
   * @param string $table
   *   (optional) The table to store file usage info. Defaults to 'file_usage'.
   *
   * @todo Properly type-hint the constructor arguments in
   *   https://www.drupal.org/project/drupal/issues/3070114 when the
   *   drupal:9.0.x branch is opened.
   */
  // @codingStandardsIgnoreLine
  public function __construct($config_factory, $connection = NULL, $table = 'file_usage') {

    // @todo Remove below conditional when the drupal:9.0.x branch is opened.
    // @see https://www.drupal.org/project/drupal/issues/3070114
    if (!$config_factory instanceof ConfigFactoryInterface) {
      @trigger_error('Passing the database connection as the first argument to ' . __METHOD__ . ' is deprecated in drupal:8.8.0 and will throw a fatal error in drupal:9.0.0. Pass the config factory first. See https://www.drupal.org/node/3070148', E_USER_DEPRECATED);
      if (!$config_factory instanceof Connection) {
        throw new \InvalidArgumentException("The first argument to " . __METHOD__ . " should be an instance of \Drupal\Core\Config\ConfigFactoryInterface, " . gettype($config_factory) . " given.");
      }
      list($connection, $table, $config_factory) = array_pad(func_get_args(), 3, NULL);
      if (NULL === $table) {
        $table = 'file_usage';
      }
      if (!$config_factory instanceof ConfigFactoryInterface) {
        $config_factory = \Drupal::configFactory();
      }
    }

    parent::__construct($config_factory);
    $this->connection = $connection;
    $this->tableName = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
    $this->connection->merge($this->tableName)
      ->keys([
        'fid' => $file->id(),
        'module' => $module,
        'type' => $type,
        'id' => $id,
      ])
      ->fields(['count' => $count])
      ->expression('count', 'count + :count', [':count' => $count])
      ->execute();

    parent::add($file, $module, $type, $id, $count);
  }

  /**
   * {@inheritdoc}
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
      $query->expression('count', 'count - :count', [':count' => $count]);
      $query->execute();
    }

    parent::delete($file, $module, $type, $id, $count);
  }

  /**
   * {@inheritdoc}
   */
  public function listUsage(FileInterface $file) {
    $result = $this->connection->select($this->tableName, 'f')
      ->fields('f', ['module', 'type', 'id', 'count'])
      ->condition('fid', $file->id())
      ->condition('count', 0, '>')
      ->execute();
    $references = [];
    foreach ($result as $usage) {
      $references[$usage->module][$usage->type][$usage->id] = $usage->count;
    }
    return $references;
  }

}
