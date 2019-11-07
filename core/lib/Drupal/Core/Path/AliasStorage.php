<?php

namespace Drupal\Core\Path;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;

@trigger_error('\Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use the "path_alias.repository" service instead, or the entity storage handler for the "path_alias" entity type for CRUD methods. See https://www.drupal.org/node/3013865.', E_USER_DEPRECATED);

/**
 * Provides a class for CRUD operations on path aliases.
 *
 * All queries perform case-insensitive matching on the 'source' and 'alias'
 * fields, so the aliases '/test-alias' and '/test-Alias' are considered to be
 * the same, and will both refer to the same internal system path.
 *
 * @deprecated \Drupal\Core\Path\AliasStorage is deprecated in drupal:8.8.0 and
 *   is removed from drupal:9.0.0. Use the "path_alias.repository" service
 *   instead, or the entity storage handler for the "path_alias" entity type
 *   for CRUD methods.
 *
 * @see https://www.drupal.org/node/3013865
 */
class AliasStorage implements AliasStorageInterface {

  /**
   * The table for the url_alias storage.
   */
  const TABLE = 'path_alias';

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Path CRUD object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection for reading and writing path aliases.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Connection $connection, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->connection = $connection;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::entityTypeManager();
  }

  /**
   * {@inheritdoc}
   */
  public function save($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid = NULL) {
    if ($source[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Source path %s has to start with a slash.', $source));
    }

    if ($alias[0] !== '/') {
      throw new \InvalidArgumentException(sprintf('Alias path %s has to start with a slash.', $alias));
    }

    if ($pid) {
      /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
      $path_alias = $this->getPathAliasEntityStorage()->load($pid);
      $original_values = [
        'source' => $path_alias->getPath(),
        'alias' => $path_alias->getAlias(),
        'langcode' => $path_alias->get('langcode')->value,
      ];

      $path_alias->setPath($source);
      $path_alias->setAlias($alias);
      $path_alias->set('langcode', $langcode);
    }
    else {
      $path_alias = $this->getPathAliasEntityStorage()->create([
        'path' => $source,
        'alias' => $alias,
        'langcode' => $langcode,
      ]);
    }

    $path_alias->save();

    $path_alias_values = [
      'pid' => $path_alias->id(),
      'source' => $path_alias->getPath(),
      'alias' => $path_alias->getAlias(),
      'langcode' => $path_alias->get('langcode')->value,
    ];

    if (isset($original_values)) {
      $path_alias_values['original'] = $original_values;
    }

    return $path_alias_values;
  }

  /**
   * {@inheritdoc}
   */
  public function load($conditions) {
    $query = $this->getPathAliasEntityStorage()->getQuery();
    // Ignore access restrictions for this API.
    $query->accessCheck(FALSE);
    foreach ($conditions as $field => $value) {
      if ($field === 'source') {
        $field = 'path';
      }
      elseif ($field === 'pid') {
        $field = 'id';
      }

      $query->condition($field, $value, '=');
    }

    $result = $query
      ->sort('id', 'DESC')
      ->range(0, 1)
      ->execute();
    $entities = $this->getPathAliasEntityStorage()->loadMultiple($result);

    /** @var \Drupal\path_alias\PathAliasInterface $path_alias */
    $path_alias = reset($entities);
    if ($path_alias) {
      return [
        'pid' => $path_alias->id(),
        'source' => $path_alias->getPath(),
        'alias' => $path_alias->getAlias(),
        'langcode' => $path_alias->get('langcode')->value,
      ];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($conditions) {
    $storage = $this->getPathAliasEntityStorage();
    $query = $storage->getQuery();
    // API functions should be able to access all entities regardless of access
    // restrictions. Those need to happen on a higher level.
    $query->accessCheck(FALSE);
    foreach ($conditions as $field => $value) {
      if ($field === 'source') {
        $field = 'path';
      }
      elseif ($field === 'pid') {
        $field = 'id';
      }

      $query->condition($field, $value, '=');
    }

    $result = $query->execute();
    $storage->delete($storage->loadMultiple($result));
  }

  /**
   * Returns a SELECT query for the path_alias base table.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   A Select query object.
   */
  protected function getBaseQuery() {
    $query = $this->connection->select(static::TABLE, 'base_table');
    $query->condition('base_table.status', 1);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function preloadPathAlias($preloaded, $langcode) {
    $select = $this->getBaseQuery()
      ->fields('base_table', ['path', 'alias']);

    if (!empty($preloaded)) {
      $conditions = new Condition('OR');
      foreach ($preloaded as $preloaded_item) {
        $conditions->condition('base_table.path', $this->connection->escapeLike($preloaded_item), 'LIKE');
      }
      $select->condition($conditions);
    }

    $this->addLanguageFallback($select, $langcode);

    // We order by ID ASC so that fetchAllKeyed() returns the most recently
    // created alias for each source. Subsequent queries using fetchField() must
    // use ID DESC to have the same effect.
    $select->orderBy('base_table.id', 'ASC');

    return $select->execute()->fetchAllKeyed();
  }

  /**
   * {@inheritdoc}
   */
  public function lookupPathAlias($path, $langcode) {
    // See the queries above. Use LIKE for case-insensitive matching.
    $select = $this->getBaseQuery()
      ->fields('base_table', ['alias'])
      ->condition('base_table.path', $this->connection->escapeLike($path), 'LIKE');

    $this->addLanguageFallback($select, $langcode);

    $select->orderBy('base_table.id', 'DESC');

    return $select->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function lookupPathSource($alias, $langcode) {
    // See the queries above. Use LIKE for case-insensitive matching.
    $select = $this->getBaseQuery()
      ->fields('base_table', ['path'])
      ->condition('base_table.alias', $this->connection->escapeLike($alias), 'LIKE');

    $this->addLanguageFallback($select, $langcode);

    $select->orderBy('base_table.id', 'DESC');

    return $select->execute()->fetchField();
  }

  /**
   * Adds path alias language fallback conditions to a select query object.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   A Select query object.
   * @param string $langcode
   *   Language code to search the path with. If there's no path defined for
   *   that language it will search paths without language.
   */
  protected function addLanguageFallback(SelectInterface $query, $langcode) {
    // Always get the language-specific alias before the language-neutral one.
    // For example 'de' is less than 'und' so the order needs to be ASC, while
    // 'xx-lolspeak' is more than 'und' so the order needs to be DESC.
    $langcode_list = [$langcode, LanguageInterface::LANGCODE_NOT_SPECIFIED];
    if ($langcode === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      array_pop($langcode_list);
    }
    elseif ($langcode > LanguageInterface::LANGCODE_NOT_SPECIFIED) {
      $query->orderBy('base_table.langcode', 'DESC');
    }
    else {
      $query->orderBy('base_table.langcode', 'ASC');
    }
    $query->condition('base_table.langcode', $langcode_list, 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function aliasExists($alias, $langcode, $source = NULL) {
    // Use LIKE and NOT LIKE for case-insensitive matching.
    $query = $this->connection->select(static::TABLE)
      ->condition('alias', $this->connection->escapeLike($alias), 'LIKE')
      ->condition('langcode', $langcode);
    if (!empty($source)) {
      $query->condition('path', $this->connection->escapeLike($source), 'NOT LIKE');
    }
    $query->addExpression('1');
    $query->range(0, 1);

    return (bool) $query->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function languageAliasExists() {
    return (bool) $this->connection->queryRange('SELECT 1 FROM {' . static::TABLE . '} WHERE langcode <> :langcode', 0, 1, [':langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasesForAdminListing($header, $keys = NULL) {
    $query = $this->connection->select(static::TABLE)
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    if ($keys) {
      // Replace wildcards with PDO wildcards.
      $query->condition('alias', '%' . preg_replace('!\*+!', '%', $keys) . '%', 'LIKE');
    }

    $query->addField(static::TABLE, 'id', 'pid');
    $query->addField(static::TABLE, 'path', 'source');
    return $query
      ->fields(static::TABLE, ['alias', 'langcode'])
      ->orderByHeader($header)
      ->limit(50)
      ->execute()
      ->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function pathHasMatchingAlias($initial_substring) {
    if (!$this->moduleHandler->moduleExists('path_alias')) {
      return FALSE;
    }

    $query = $this->getBaseQuery();
    $query->addExpression(1);

    return (bool) $query
      ->condition('base_table.path', $this->connection->escapeLike($initial_substring) . '%', 'LIKE')
      ->range(0, 1)
      ->execute()
      ->fetchField();
  }

  /**
   * Returns the path alias entity storage handler.
   *
   * We can not store it in the constructor because that leads to a circular
   * dependency in the service container.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The path alias entity storage.
   */
  protected function getPathAliasEntityStorage() {
    return $this->entityTypeManager->getStorage('path_alias');
  }

}
