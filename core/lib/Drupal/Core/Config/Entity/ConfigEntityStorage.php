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
   * Constructs a ConfigEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, StorageInterface $config_storage, UuidInterface $uuid_service, LanguageManagerInterface $language_manager) {
    parent::__construct($entity_type);

    $this->configFactory = $config_factory;
    $this->configStorage = $config_storage;
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
      $container->get('config.storage'),
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
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
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
    $prefix = $this->getConfigPrefix();

    // Get the names of the configuration entities we are going to load.
    if ($ids === NULL) {
      $names = $this->configStorage->listAll($prefix);
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
      $config = $this->configFactory->get($this->getConfigPrefix() . $entity->id());
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
    $prefix = $this->getConfigPrefix();
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
    foreach ($entity->toArray() as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    return $is_new ? SAVED_NEW : SAVED_UPDATED;
  }

  /**
   * {@inheritdoc}
   */
  protected function has($id, EntityInterface $entity) {
    $prefix = $this->getConfigPrefix();
    $config = $this->configFactory->get($prefix . $id);
    return !$config->isNew();
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
    $entity = $this->create($new_config->get());
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
    $entity->original = clone $entity;

    foreach ($old_config->get() as $property => $value) {
      $entity->original->set($property, $value);
    }

    foreach ($new_config->get() as $property => $value) {
      $entity->set($property, $value);
    }

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
    $id = static::getIDFromConfigName($old_name, $this->entityType->getConfigPrefix());
    $entity = $this->load($id);
    $entity->setSyncing(TRUE);
    $data = $new_config->get();
    foreach ($data as $key => $value) {
      $entity->set($key, $value);
    }
    $entity->save();
    return TRUE;
  }

}
