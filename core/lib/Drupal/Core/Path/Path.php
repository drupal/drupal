<?php

/**
 * @file
 * Contains Drupal\Core\Path\Path.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;

/**
 * Defines a class for CRUD operations on path aliases.
 */
class Path {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a Path CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Saves a path alias to the database.
   *
   * @param string $source
   *   The internal system path.
   *
   * @param string $alias
   *   The URL alias.
   *
   * @param string $langcode
   *   The language code of the alias.
   *
   * @param int $pid
   *   Unique path alias identifier.
   *
   * @return
   *   FALSE if the path could not be saved or an associative array containing
   *   the following keys:
   *   - source: The internal system path.
   *   - alias: The URL alias.
   *   - pid: Unique path alias identifier.
   *   - langcode: The language code of the alias.
   */
  public function save($source, $alias, $langcode = Language::LANGCODE_NOT_SPECIFIED, $pid = NULL) {

    $fields = array(
      'source' => $source,
      'alias' => $alias,
      'langcode' => $langcode,
    );

    // Insert or update the alias.
    if (empty($pid)) {
      $query = $this->connection->insert('url_alias')
        ->fields($fields);
      $pid = $query->execute();
      $fields['pid'] = $pid;
      $operation = 'insert';
    }
    else {
      $fields['pid'] = $pid;
      $query = $this->connection->update('url_alias')
        ->fields($fields)
        ->condition('pid', $pid);
      $pid = $query->execute();
      $operation = 'update';
    }
    if ($pid) {
      // @todo Switch to using an event for this instead of a hook.
      $this->moduleHandler->invokeAll('path_' . $operation, array($fields));
      return $fields;
    }
    return FALSE;
  }

  /**
   * Fetches a specific URL alias from the database.
   *
   * @param $conditions
   *   An array of query conditions.
   *
   * @return
   *   FALSE if no alias was found or an associative array containing the
   *   following keys:
   *   - source: The internal system path.
   *   - alias: The URL alias.
   *   - pid: Unique path alias identifier.
   *   - langcode: The language code of the alias.
   */
  public function load($conditions) {
    $select = $this->connection->select('url_alias');
    foreach ($conditions as $field => $value) {
      $select->condition($field, $value);
    }
    return $select
      ->fields('url_alias')
      ->execute()
      ->fetchAssoc();
  }

  /**
   * Deletes a URL alias.
   *
   * @param array $conditions
   *   An array of criteria.
   */
  public function delete($conditions) {
    $path = $this->load($conditions);
    $query = $this->connection->delete('url_alias');
    foreach ($conditions as $field => $value) {
      $query->condition($field, $value);
    }
    $deleted = $query->execute();
    // @todo Switch to using an event for this instead of a hook.
    $this->moduleHandler->invokeAll('path_delete', array($path));
    return $deleted;
  }
}
