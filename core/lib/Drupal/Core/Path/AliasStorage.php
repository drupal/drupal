<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasStorage.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\Language;

/**
 * Provides a class for CRUD operations on path aliases.
 */
class AliasStorage implements AliasStorageInterface {
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
