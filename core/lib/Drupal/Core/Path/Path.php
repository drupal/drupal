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

  /**
   * Preloads path alias information for a given list of source paths.
   *
   * @param $path
   *   The path to investigate for corresponding aliases.
   * @param $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   * @return array
   *   Source (keys) to alias (values) mapping.
   */
  public function preloadPathAlias($preloaded, $langcode) {
    $args = array(
      ':system' => $preloaded,
      ':langcode' => $langcode,
      ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
    );
    // Always get the language-specific alias before the language-neutral one.
    // For example 'de' is less than 'und' so the order needs to be ASC, while
    // 'xx-lolspeak' is more than 'und' so the order needs to be DESC. We also
    // order by pid ASC so that fetchAllKeyed() returns the most recently
    // created alias for each source. Subsequent queries using fetchField() must
    // use pid DESC to have the same effect. For performance reasons, the query
    // builder is not used here.
    if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
      // Prevent PDO from complaining about a token the query doesn't use.
      unset($args[':langcode']);
      $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode = :langcode_undetermined ORDER BY pid ASC', $args);
    }
    elseif ($langcode < Language::LANGCODE_NOT_SPECIFIED) {
      $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid ASC', $args);
    }
    else {
      $result = $this->connection->query('SELECT source, alias FROM {url_alias} WHERE source IN (:system) AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid ASC', $args);
    }

    return $result->fetchAllKeyed();
  }

  /**
   * Returns an alias of Drupal system URL.
   *
   * @param string $path
   *   The path to investigate for corresponding path aliases.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string|bool
   *   A path alias, or FALSE if no path was found.
   */
  public function lookupPathAlias($path, $langcode) {
    $args = array(
      ':source' => $path,
      ':langcode' => $langcode,
      ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
    );
    // See the queries above.
    if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
      unset($args[':langcode']);
      $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode = :langcode_undetermined ORDER BY pid DESC", $args)->fetchField();
    }
    elseif ($langcode > Language::LANGCODE_NOT_SPECIFIED) {
      $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid DESC", $args)->fetchField();
    }
    else {
      $alias = $this->connection->query("SELECT alias FROM {url_alias} WHERE source = :source AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid DESC", $args)->fetchField();
    }

    return $alias;
  }

  /**
   * Returns Drupal system URL of an alias.
   *
   * @param string $path
   *   The path to investigate for corresponding system URLs.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   *
   * @return string|bool
   *   A Drupal system path, or FALSE if no path was found.
   */
  public function lookupPathSource($path, $langcode) {
    $args = array(
      ':alias' => $path,
      ':langcode' => $langcode,
      ':langcode_undetermined' => Language::LANGCODE_NOT_SPECIFIED,
    );
    // See the queries above.
    if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
      unset($args[':langcode']);
      $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode = :langcode_undetermined ORDER BY pid DESC", $args);
    }
    elseif ($langcode > Language::LANGCODE_NOT_SPECIFIED) {
      $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode DESC, pid DESC", $args);
    }
    else {
      $result = $this->connection->query("SELECT source FROM {url_alias} WHERE alias = :alias AND langcode IN (:langcode, :langcode_undetermined) ORDER BY langcode ASC, pid DESC", $args);
    }

    return $result->fetchField();
  }
}
