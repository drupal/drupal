<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageBase;
use Drupal\Core\Config\Config;
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
   * Determines if the underlying configuration is retrieved override free.
   *
   * @var bool
   */
  protected $overrideFree = FALSE;

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
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache) {
    parent::__construct($entity_type, $memory_cache);

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
      $container->get('language_manager'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadRevision($revision_id) {
    return NULL;
  }

  /**
   * {@inheritdoc}
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
      $names = [];
      foreach ($ids as $id) {
        // Add the prefix to the ID to serve as the configuration object name.
        $names[] = $prefix . $id;
      }
    }

    // Load all of the configuration entities.
    /** @var \Drupal\Core\Config\Config[] $configs */
    $configs = [];
    $records = [];
    foreach ($this->configFactory->loadMultiple($names) as $config) {
      $id = $config->get($this->idKey);
      $records[$id] = $this->overrideFree ? $config->getOriginal(NULL, FALSE) : $config->get();
      $configs[$id] = $config;
    }
    $entities = $this->mapFromStorageRecords($records);

    // Config entities wrap config objects, and therefore they need to inherit
    // the cacheability metadata of config objects (to ensure e.g. additional
    // cacheability metadata added by config overrides is not lost).
    foreach ($entities as $id => $entity) {
      // But rather than simply inheriting all cacheability metadata of config
      // objects, we need to make sure the self-referring cache tag that is
      // present on Config objects is not added to the Config entity. It must be
      // removed for 3 reasons:
      // 1. When renaming/duplicating a Config entity, the cache tag of the
      //    original config object would remain present, which would be wrong.
      // 2. Some Config entities choose to not use the cache tag that the under-
      //    lying Config object provides by default (For performance and
      //    cacheability reasons it may not make sense to have a unique cache
      //    tag for every Config entity. The DateFormat Config entity specifies
      //    the 'rendered' cache tag for example, because A) date formats are
      //    changed extremely rarely, so invalidating all render cache items is
      //    fine, B) it means fewer cache tags per page.).
      // 3. Fewer cache tags is better for performance.
      $self_referring_cache_tag = ['config:' . $configs[$id]->getName()];
      $config_cacheability = CacheableMetadata::createFromObject($configs[$id]);
      $config_cacheability->setCacheTags(array_diff($config_cacheability->getCacheTags(), $self_referring_cache_tag));
      $entity->addCacheableDependency($config_cacheability);
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // Set default language to current language if not provided.
    $values += [$this->langcodeKey => $this->languageManager->getCurrentLanguage()->getId()];
    $entity = new $this->entityClass($values, $this->entityTypeId);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    foreach ($entities as $entity) {
      $this->configFactory->getEditable($this->getPrefix() . $entity->id())->delete();
    }
  }

  /**
   * Implements Drupal\Core\Entity\EntityStorageInterface::save().
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
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
    if (strlen($entity->get($this->idKey)) > static::MAX_ID_LENGTH) {
      throw new ConfigEntityIdLengthException("Configuration entity ID {$entity->get($this->idKey)} exceeds maximum allowed length of " . static::MAX_ID_LENGTH . " characters.");
    }

    return parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    $is_new = $entity->isNew();
    $prefix = $this->getPrefix();
    $config_name = $prefix . $entity->id();
    if ($id !== $entity->id()) {
      // Renaming a config object needs to cater for:
      // - Storage needs to access the original object.
      // - The object needs to be renamed/copied in ConfigFactory and reloaded.
      // - All instances of the object need to be renamed.
      $this->configFactory->rename($prefix . $id, $config_name);
    }
    $config = $this->configFactory->getEditable($config_name);

    // Retrieve the desired properties and set them in config.
    $config->setData($this->mapToStorageRecord($entity));
    $config->save($entity->hasTrustedData());

    // Update the entity with the values stored in configuration. It is possible
    // that configuration schema has casted some of the values.
    if (!$entity->hasTrustedData()) {
      $data = $this->mapFromStorageRecords([$config->get()]);
      $updated_entity = current($data);

      foreach (array_keys($config->get()) as $property) {
        $value = $updated_entity->get($property);
        $entity->set($property, $value);
      }
    }

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
   * {@inheritdoc}
   */
  public function hasData() {
    return (bool) $this->configFactory->listAll($this->getPrefix());
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCacheId($id) {
    return parent::buildCacheId($id) . ':' . ($this->overrideFree ? '' : implode(':', $this->configFactory->getCacheKeys()));
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
    $this->moduleHandler->invokeAll($this->entityTypeId . '_' . $hook, [$entity]);
    // Invoke the respective entity-level hook.
    $this->moduleHandler->invokeAll('entity_' . $hook, [$entity, $this->entityTypeId]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.config';
  }

  /**
   * {@inheritdoc}
   */
  public function importCreate($name, Config $new_config, Config $old_config) {
    $entity = $this->_doCreateFromStorageRecord($new_config->get(), TRUE);
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
      throw new ConfigImporterException("Attempt to update non-existing entity '$id'.");
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
    return $this->_doCreateFromStorageRecord($values);
  }

  /**
   * Helps create a configuration entity from storage values.
   *
   * Allows the configuration entity storage to massage storage values before
   * creating an entity.
   *
   * @param array $values
   *   The array of values from the configuration storage.
   * @param bool $is_syncing
   *   Is the configuration entity being created as part of a config sync.
   *
   * @return \Drupal\Core\Config\ConfigEntityInterface
   *   The configuration entity.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityStorageInterface::createFromStorageRecord()
   * @see \Drupal\Core\Config\Entity\ImportableEntityStorageInterface::importCreate()
   */
  protected function _doCreateFromStorageRecord(array $values, $is_syncing = FALSE) {
    // Assign a new UUID if there is none yet.
    if ($this->uuidKey && $this->uuidService && !isset($values[$this->uuidKey])) {
      $values[$this->uuidKey] = $this->uuidService->generate();
    }
    $data = $this->mapFromStorageRecords([$values]);
    $entity = current($data);
    $entity->original = clone $entity;
    $entity->setSyncing($is_syncing);
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

    $data = $this->mapFromStorageRecords([$values]);
    $updated_entity = current($data);

    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $this->getEntityType();
    $id_key = $entity_type->getKey('id');
    $properties = $entity_type->getPropertiesToExport($updated_entity->get($id_key));

    if (empty($properties)) {
      // Fallback to using the provided values. If the properties cannot be
      // determined for the config entity type annotation or configuration
      // schema.
      $properties = array_keys($values);
    }
    foreach ($properties as $property) {
      if ($property === $this->uuidKey) {
        // During an update the UUID field should not be copied. Under regular
        // circumstances the values will be equal. If configuration is written
        // twice during configuration install the updated entity will not have a
        // UUID.
        // @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
        continue;
      }
      $entity->set($property, $updated_entity->get($property));
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrideFree($id) {
    $entities = $this->loadMultipleOverrideFree([$id]);
    return isset($entities[$id]) ? $entities[$id] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultipleOverrideFree(array $ids = NULL) {
    $this->overrideFree = TRUE;
    $entities = $this->loadMultiple($ids);
    $this->overrideFree = FALSE;
    return $entities;
  }

}
