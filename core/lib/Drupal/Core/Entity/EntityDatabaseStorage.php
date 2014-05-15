<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDatabaseStorage.
 */

namespace Drupal\Core\Entity;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base entity controller class.
 *
 * This class only supports bare, non-content entities.
 */
class EntityDatabaseStorage extends EntityStorageBase {

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * Whether this entity type should use the static cache.
   *
   * @var boolean
   */
  protected $cache;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('uuid')
    );
  }

  /**
   * Constructs a EntityDatabaseStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, UuidInterface $uuid_service) {
    parent::__construct($entity_type);

    $this->database = $database;
    $this->uuidService = $uuid_service;

    // Check if the entity type supports UUIDs.
    $this->uuidKey = $this->entityType->getKey('uuid');
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    // Build and execute the query.
    $records = $this
      ->buildQuery($ids)
      ->execute()
      ->fetchAllAssoc($this->idKey, \PDO::FETCH_ASSOC);

    return $this->mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    throw new \Exception('Database storage does not support revisions.');
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    throw new \Exception('Database storage does not support revisions.');
  }

  /**
   * Builds the query to load the entity.
   *
   * @param array|null $ids
   *   An array of entity IDs, or NULL to load all entities.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   A SelectQuery object for loading the entity.
   */
  protected function buildQuery($ids) {
    $query = $this->database->select($this->entityType->getBaseTable(), 'base');

    $query->addTag($this->entityTypeId . '_load_multiple');

    // Add fields from the {entity} table.
    $entity_fields = drupal_schema_fields_sql($this->entityType->getBaseTable());
    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    if (!$entities) {
      // If no IDs or invalid IDs were passed, do nothing.
      return;
    }
    $transaction = $this->database->startTransaction();

    try {
      parent::delete($entities);

      // Ignore slave server temporarily.
      db_ignore_slave();
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    $ids = array_keys($entities);

    $this->database->delete($this->entityType->getBaseTable())
      ->condition($this->idKey, $ids, 'IN')
      ->execute();

    // Reset the cache as soon as the changes have been applied.
    $this->resetCache($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $transaction = $this->database->startTransaction();
    try {
      $return = parent::save($entity);

      // Ignore slave server temporarily.
      db_ignore_slave();
      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    if (!$entity->isNew()) {
      $return = drupal_write_record($this->entityType->getBaseTable(), $entity, $this->idKey);
      $this->resetCache(array($entity->id()));
    }
    else {
      $return = drupal_write_record($this->entityType->getBaseTable(), $entity);
      // Reset general caches, but keep caches specific to certain entities.
      $this->resetCache(array());
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return !$entity->isNew();
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryServiceName() {
    return 'entity.query.sql';
  }

}
