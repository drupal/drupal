<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityStorage.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage class for configuration entities.
 *
 * Configuration object names of configuration entities are comprised of two
 * parts, separated by a dot:
 * - config_prefix: A string denoting the owner (module/extension) of the
 *   configuration object, followed by arbitrary other namespace identifiers
 *   that are declared by the owning extension; e.g., 'node.type'. The
 *   config_prefix does NOT contain a trailing dot. It is defined by the entity
 *   type's annotation.
 * - ID: A string denoting the entity ID within the entity type namespace; e.g.,
 *   'article'. Entity IDs may contain dots/periods. The entire remaining string
 *   after the config_prefix in a config name forms the entity ID. Additional or
 *   custom suffixes are not possible.
 *
 * @ingroup entity_api
 */
class ConfigEntityStorage extends EntityStorageBase implements ConfigEntityStorageInterface, ImportableEntityStorageInterface {

  /**
   * Length limit of the configuration entity ID.
   *
   * Most file systems limit a file name's length to 255 characters, so
   * ConfigBase::MAX_NAME_LENGTH restricts the full configuration object name
   * to 250 characters (leaving 5 for the file extension). The config prefix
   * is limited by ConfigEntityType::PREFIX_LENGTH to 83 characters, so this
   * leaves 166 remaining characters for the configuration entity ID, with 1
   * additional character needed for the joining dot.
   *
   * @see \Drupal\Core\Config\ConfigBase::MAX_NAME_LENGTH
   * @see \Drupal\Core\Config\Entity\ConfigEntityType::PREFIX_LENGTH
   */
  const MAX_ID_LENGTH = 166;

  /**
   * {@inheritdoc}
   */
  protected $uuidKey = 'uuid';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Static cache of entities, keyed first by entity ID, then by an extra key.
   *
   * The additional cache key is to maintain separate caches for different
   * states of config overrides.
   *
   * @var array
   * @see \Drupal\Core\Config\ConfigFactoryInterface::getCacheKeys().
   */
  protected $entities = array();

  /**
   * Constructs a ConfigEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type);

    $this->configFactory = $config_factory;
    $this->uuidService = $uuid_service;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager')
    );
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageInterface::loadRevision().
   */
  public function loadRevision($revision_id) {
    return FALSE;
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageInterface::deleteRevision().
   */
  public function deleteRevision($revision_id) {
    return NULL;
  }

  /**
   * Returns the prefix used to create the configuration name.
   *
   * The prefix consists of the config prefix from the entity type plus a dot
   * for separating from the ID.
   *
   * @return string
   *   The full configuration prefix, for example 'views.view.'.
   */
  protected function getPrefix() {
    return $this->entityType->getConfigPrefix() . '.';
  }

  /**
   * {@inheritdoc}
   */
  public static function getIDFromConfigName($config_name, $config_prefix) {
    return substr($config_name, strlen($config_prefix . '.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    $prefix = $this->getPrefix();

    // Get the names of the configuration entities we are going to load.
    if ($ids === NULL) {
      $names = $this->configFactory->listAll($prefix);
    }
    else {
      $names = array();
      foreach ($ids as $id) {
        // Add the prefix to the ID to serve as the configuration object name.
        $names[] = $prefix . $id;
      }
    }

    // Load all of the configuration entities.
    $records = array();
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $records[$config->get($this->idKey)] = $config->get();
    }
    return $this->mapFromStorageRecords($records);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // Set default language to site default if not provided.
    $values += array('langcode' => $this->languageManager->getDefaultLanguage()->id);
    $entity = new $this->entityClass($values, $this->entityTypeId);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    foreach ($entities as $entity) {
      $config = $this->configFactory->get($this->getPrefix() . $entity->id());
      $config->delete();
    }
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageInterface::save().
   *
   * @throws EntityMalformedException
   *   When attempting to save a configuration entity that has no ID.
   */
  public function save(EntityInterface $entity) {
    // Configuration entity IDs are strings, and '0' is a valid ID.
    $id = $entity->id();
    if ($id === NULL || $id === '') {
      throw new EntityMalformedException('The entity does not have an ID.');
    }

    // Check the configuration entity ID length.
    // @see \Drupal\Core\Config\Entity\ConfigEntityStorage::MAX_ID_LENGTH
    // @todo Consider moving this to a protected method on the parent class, and
    //   abstracting it for all entity types.
    if (strlen($entity->get($this->idKey)) > self::MAX_ID_LENGTH) {
      throw new ConfigEntityIdLengthException(String::format('Configuration entity ID @id exceeds maximum allowed length of @length characters.', array(
        '@id' => $entity->get($this->idKey),
        '@length' => self::MAX_ID_LENGTH,
      )));
    }

    return parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $is_new = $entity->isNew();
    $prefix = $this->getPrefix();
    if ($id !== $entity->id()) {
      // Renaming a config object needs to cater for:
      // - Storage needs to access the original object.
      // - The object needs to be renamed/copied in ConfigFactory and reloaded.
      // - All instances of the object need to be renamed.
      $config = $this->configFactory->rename($prefix . $id, $prefix . $entity->id());
    }
    else {
      $config = $this->configFactory->get($prefix . $id);
    }

    // Retrieve the desired properties and set them in config.
    $record = $this->mapToStorageRecord($entity);
    foreach ($record as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

  /**
   * Maps from an entity object to the storage record.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   The record to store.
   */
  protected function mapToStorageRecord(EntityInterface $entity) {
    return $entity->toArray();
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    $prefix = $this->getPrefix();
    $config = $this->configFactory->get($prefix . $id);
    return !$config->isNew();
  }

  /**
   * Gets entities from the static cache.
   *
   * @param array $ids
   *   If not empty, return entities that match these IDs.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   Array of entities from the entity cache.
   */
  protected function getFromStaticCache(array $ids) {
    $entities = array();
    // Load any available entities from the internal cache.
    if ($this->entityType->isStaticallyCacheable() && !empty($this->entities)) {
      $config_overrides_key = implode(':', $this->configFactory->getCacheKeys());
      foreach ($ids as $id) {
        if (!empty($this->entities[$id])) {
          if (isset($this->entities[$id][$config_overrides_key])) {
            $entities[$id] = $this->entities[$id][$config_overrides_key];
          }
        }
      }
    }
    return $entities;
  }

  /**
   * Stores entities in the static entity cache.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Entities to store in the cache.
   */
  protected function setStaticCache(array $entities) {
    if ($this->entityType->isStaticallyCacheable()) {
      $config_overrides_key = implode(':', $this->configFactory->getCacheKeys());
      foreach ($entities as $id => $entity) {
        $this->entities[$id][$config_overrides_key] = $entity;
      }
    }
  }

  /**
   * Invokes a hook on behalf of the entity.
   *
   * @param $hook
   *   One of 'presave', 'insert', 'update', 'predelete', or 'delete'.
   * @param $entity
   *   The entity object.
   */
  protected function invokeHook($hook, EntityInterface $entity) {
    // Invoke the hook.
    $this->moduleHandler->invokeAll($this->entityTypeId . '_' . $hook, array($entity));
    // Invoke the respective entity-level hook.
    $this->moduleHandler->invokeAll('entity_' . $hook, array($entity, $this->entityTypeId));
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageInterface::getQueryServicename().
   */
  public function getQueryServicename() {
    return 'entity.query.config';
  }

  /**
   * {@inheritdoc}
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    $entity = $this->createFromStorageRecord($new_config->get());
    $entity->setSyncing(TRUE);
    $entity->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function importUpdate($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityType->getConfigPrefix());
    $entity = $this->load($id);
    if (!$entity) {
      throw new ConfigImporterException(String::format('Attempt to update non-existing entity "@id".', array('@id' => $id)));
    }
    $entity->setSyncing(TRUE);
    $entity = $this->updateFromStorageRecord($entity, $new_config->get());
    $entity->save();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function importDelete($name, Config $new_config, Config $old_config) {
    $id = static::getIDFromConfigName($name, $this->entityType->getConfigPrefix());
    $entity = $this->load($id);
    $entity->setSyncing(TRUE);
    $entity->delete();
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function importRename($old_name, Config $new_config, Config $old_config) {
    return $this->importUpdate($old_name, $new_config, $old_config);
  }

  /**
   * {@inheritdoc}
   */
  public function createFromStorageRecord(array $values) {
    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && $this->uuidService && !isset($values[$this->uuidKey])) {
      $values[$this->uuidKey] = $this->uuidService->generate();
    }
    $data = $this->mapFromStorageRecords(array($values));
    $entity = current($data);
    $entity->original = clone $entity;
    $entity->enforceIsNew();
    $entity->postCreate($this);

    // Modules might need to add or change the data initially held by the new
    // entity object, for instance to fill-in default values.
    $this->invokeHook('create', $entity);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function updateFromStorageRecord(ConfigEntityInterface $entity, array $values) {
    $entity->original = clone $entity;

    $data = $this->mapFromStorageRecords(array($values));
    $updated_entity = current($data);

    foreach (array_keys($values) as $property) {
      $value = $updated_entity->get($property);
      $entity->set($property, $value);
    }

    return $entity;
  }

}
