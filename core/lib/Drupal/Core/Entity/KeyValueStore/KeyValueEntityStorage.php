<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage.
 */

namespace Drupal\Core\Entity\KeyValueStore;

use Drupal\Component\Utility\String;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a key value backend for entities.
 *
 * @todo Entities that depend on auto-incrementing serial IDs need to explicitly
 *   provide an ID until a generic wrapper around the functionality provided by
 *   \Drupal\Core\Database\Connection::nextId() is added and used.
 * @todo Revisions are currently not supported.
 */
class KeyValueEntityStorage extends EntityStorageBase {

  /**
   * Length limit of the entity ID.
   */
  const MAX_ID_LENGTH = 128;

  /**
   * The key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValueStore;

  /**
   * The UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new KeyValueEntityStorage.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreInterface $key_value_store
   *   The key value store.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, KeyValueStoreInterface $key_value_store, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type);
    $this->keyValueStore = $key_value_store;
    $this->uuidService = $uuid_service;
    $this->languageManager = $language_manager;

    // Check if the entity type supports UUIDs.
    $this->uuidKey = $this->entityType->getKey('uuid');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('keyvalue')->get('entity_storage__' . $entity_type->id()),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function doCreate(array $values = array()) {
    // Set default language to site default if not provided.
    $values += array('langcode' => $this->languageManager->getDefaultLanguage()->id);
    $entity = new $this->entityClass($values, $this->entityTypeId);

    // @todo This is handled by ContentEntityStorageBase, which assumes
    //   FieldableEntityInterface. The current approach in
    //   https://drupal.org/node/1867228 improves this but does not solve it
    //   completely.
    if ($entity instanceof FieldableEntityInterface) {
      foreach ($entity as $name => $field) {
        if (isset($values[$name])) {
          $entity->$name = $values[$name];
        }
        elseif (!array_key_exists($name, $values)) {
          $entity->get($name)->applyDefaultValue();
        }
        unset($values[$name]);
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function doLoadMultiple(array $ids = NULL) {
    if (empty($ids)) {
      $entities = $this->keyValueStore->getAll();
    }
    else {
      $entities = $this->keyValueStore->getMultiple($ids);
    }
    return $this->mapFromStorageRecords($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function doDelete($entities) {
    $entity_ids = array();
    foreach ($entities as $entity) {
      $entity_ids[] = $entity->id();
    }
    $this->keyValueStore->deleteMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $id = $entity->id();
    if ($id === NULL || $id === '') {
      throw new EntityMalformedException('The entity does not have an ID.');
    }

    // Check the entity ID length.
    // @todo This is not config-specific, but serial IDs will likely never hit
    //   this limit. Consider renaming the exception class.
    if (strlen($entity->id()) > static::MAX_ID_LENGTH) {
      throw new ConfigEntityIdLengthException(String::format('Entity ID @id exceeds maximum allowed length of @length characters.', array(
        '@id' => $entity->id(),
        '@length' => static::MAX_ID_LENGTH,
      )));
    }
    return parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $is_new = $entity->isNew();

    // Save the entity data in the key value store.
    $this->keyValueStore->set($entity->id(), $entity->toArray());

    // If this is a rename, delete the original entity.
    if ($this->has($id, $entity) && $id !== $entity->id()) {
      $this->keyValueStore->delete($id);
    }

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    return $this->keyValueStore->has($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryServicename() {
    return 'entity.query.keyvalue';
  }

}
