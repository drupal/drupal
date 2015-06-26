<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityBase.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigException;
use Drupal\Core\Config\Schema\SchemaIncompleteException;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\ConfigDuplicateUUIDException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;

/**
 * Defines a base configuration entity class.
 *
 * @ingroup entity_api
 */
abstract class ConfigEntityBase extends Entity implements ConfigEntityInterface {

  use PluginDependencyTrait {
    addDependency as addDependencyTrait;
  }

  /**
   * The original ID of the configuration entity.
   *
   * The ID of a configuration entity is a unique string (machine name). When a
   * configuration entity is updated and its machine name is renamed, the
   * original ID needs to be known.
   *
   * @var string
   */
  protected $originalId;

  /**
   * The name of the property that is used to store plugin configuration.
   *
   * This is needed when the entity uses a LazyPluginCollection, to dictate
   * where the plugin configuration should be stored.
   *
   * @var string
   */
  protected $pluginConfigKey;

  /**
   * The enabled/disabled status of the configuration entity.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The UUID for this entity.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Whether the config is being created, updated or deleted through the
   * import process.
   *
   * @var bool
   */
  private $isSyncing = FALSE;

  /**
   * Whether the config is being deleted by the uninstall process.
   *
   * @var bool
   */
  private $isUninstalling = FALSE;

  /**
   * The language code of the entity's default language.
   *
   * Assumed to be English by default. ConfigEntityStorage will set an
   * appropriate language when creating new entities. This default applies to
   * imported default configuration where the language code is missing. Those
   * should be assumed to be English. All configuration entities support third
   * party settings, so even configuration entities that do not directly
   * store settings involving text in a human language may have such third
   * party settings attached. This means configuration entities should be in one
   * of the configured languages or the built-in English.
   *
   * @var string
   */
  protected $langcode = 'en';

  /**
   * Third party entity settings.
   *
   * An array of key/value pairs keyed by provider.
   *
   * @var array
   */
  protected $third_party_settings = array();

  /**
   * Trust supplied data and not use configuration schema on save.
   *
   * @var bool
   */
  protected $trustedData = FALSE;

  /**
   * Overrides Entity::__construct().
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    // Backup the original ID, if any.
    // Configuration entity IDs are strings, and '0' is a valid ID.
    $original_id = $this->id();
    if ($original_id !== NULL && $original_id !== '') {
      $this->setOriginalId($original_id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalId() {
    return $this->originalId;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalId($id) {
    // Do not call the parent method since that would mark this entity as no
    // longer new. Unlike content entities, new configuration entities have an
    // ID.
    // @todo https://www.drupal.org/node/2478811 Document the entity life cycle
    //   and the differences between config and content.
    $this->originalId = $id;
    return $this;
  }

  /**
   * Overrides Entity::isNew().
   *
   * EntityInterface::enforceIsNew() is only supported for newly created
   * configuration entities but has no effect after saving, since each
   * configuration entity is unique.
   */
  public function isNew() {
    return !empty($this->enforceIsNew);
  }

  /**
   * {@inheritdoc}
   */
  public function get($property_name) {
    return isset($this->{$property_name}) ? $this->{$property_name} : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    if ($this instanceof EntityWithPluginCollectionInterface) {
      $plugin_collections = $this->getPluginCollections();
      if (isset($plugin_collections[$property_name])) {
        // If external code updates the settings, pass it along to the plugin.
        $plugin_collections[$property_name]->setConfiguration($value);
      }
    }

    $this->{$property_name} = $value;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    return $this->setStatus(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    // An entity was disabled, invalidate its own cache tag.
    Cache::invalidateTags($this->getCacheTags());
    return $this->setStatus(FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->status = (bool) $status;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return !empty($this->status);
  }

  /**
   * {@inheritdoc}
   */
  public function setSyncing($syncing) {
    $this->isSyncing = $syncing;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->isSyncing;
  }

  /**
   * {@inheritdoc}
   */
  public function setUninstalling($uninstalling) {
    $this->isUninstalling = $uninstalling;
  }

  /**
   * {@inheritdoc}
   */
  public function isUninstalling() {
    return $this->isUninstalling;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    $duplicate = parent::createDuplicate();

    // Prevent the new duplicate from being misinterpreted as a rename.
    $duplicate->setOriginalId(NULL);
    return $duplicate;
  }

  /**
   * Helper callback for uasort() to sort configuration entities by weight and label.
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_weight = isset($a->weight) ? $a->weight : 0;
    $b_weight = isset($b->weight) ? $b->weight : 0;
    if ($a_weight == $b_weight) {
      $a_label = $a->label();
      $b_label = $b->label();
      return strnatcasecmp($a_label, $b_label);
    }
    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $properties = array();
    /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
    $entity_type = $this->getEntityType();

    $properties_to_export = $entity_type->getPropertiesToExport();
    if (empty($properties_to_export)) {
      $config_name = $entity_type->getConfigPrefix() . '.' . $this->id();
      $definition = $this->getTypedConfig()->getDefinition($config_name);
      if (!isset($definition['mapping'])) {
        throw new SchemaIncompleteException(SafeMarkup::format('Incomplete or missing schema for @config_name', array('@config_name' => $config_name)));
      }
      $properties_to_export = array_combine(array_keys($definition['mapping']), array_keys($definition['mapping']));
    }

    $id_key = $entity_type->getKey('id');
    foreach ($properties_to_export as $property_name => $export_name) {
      // Special handling for IDs so that computed compound IDs work.
      // @see \Drupal\Core\Entity\EntityDisplayBase::id()
      if ($property_name == $id_key) {
        $properties[$export_name] = $this->id();
      }
      else {
        $properties[$export_name] = $this->get($property_name);
      }
    }

    if (empty($this->third_party_settings)) {
      unset($properties['third_party_settings']);
    }
    return $properties;
  }

  /**
   * Gets the typed config manager.
   *
   * @return \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected function getTypedConfig() {
    return \Drupal::service('config.typed');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this instanceof EntityWithPluginCollectionInterface) {
      // Any changes to the plugin configuration must be saved to the entity's
      // copy as well.
      foreach ($this->getPluginCollections() as $plugin_config_key => $plugin_collection) {
        $this->set($plugin_config_key, $plugin_collection->getConfiguration());
      }
    }

    // Ensure this entity's UUID does not exist with a different ID, regardless
    // of whether it's new or updated.
    $matching_entities = $storage->getQuery()
      ->condition('uuid', $this->uuid())
      ->execute();
    $matched_entity = reset($matching_entities);
    if (!empty($matched_entity) && ($matched_entity != $this->id()) && $matched_entity != $this->getOriginalId()) {
      throw new ConfigDuplicateUUIDException(SafeMarkup::format('Attempt to save a configuration entity %id with UUID %uuid when this UUID is already used for %matched', array('%id' => $this->id(), '%uuid' => $this->uuid(), '%matched' => $matched_entity)));
    }

    // If this entity is not new, load the original entity for comparison.
    if (!$this->isNew()) {
      $original = $storage->loadUnchanged($this->getOriginalId());
      // Ensure that the UUID cannot be changed for an existing entity.
      if ($original && ($original->uuid() != $this->uuid())) {
        throw new ConfigDuplicateUUIDException(SafeMarkup::format('Attempt to save a configuration entity %id with UUID %uuid when this entity already exists with UUID %original_uuid', array('%id' => $this->id(), '%uuid' => $this->uuid(), '%original_uuid' => $original->uuid())));
      }
    }
    if (!$this->isSyncing() && !$this->trustedData) {
      // Ensure the correct dependencies are present. If the configuration is
      // being written during a configuration synchronization then there is no
      // need to recalculate the dependencies.
      $this->calculateDependencies();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // Dependencies should be recalculated on every save. This ensures stale
    // dependencies are never saved.
    if (isset($this->dependencies['enforced'])) {
      $dependencies = $this->dependencies['enforced'];
      $this->dependencies = $dependencies;
      $this->dependencies['enforced'] = $dependencies;
    }
    else {
      $this->dependencies = array();
    }
    if ($this instanceof EntityWithPluginCollectionInterface) {
      // Configuration entities need to depend on the providers of any plugins
      // that they store the configuration for.
      foreach ($this->getPluginCollections() as $plugin_collection) {
        foreach ($plugin_collection as $instance) {
          $this->calculatePluginDependencies($instance);
        }
      }
    }
    if ($this instanceof ThirdPartySettingsInterface) {
      // Configuration entities need to depend on the providers of any third
      // parties that they store the configuration for.
      foreach ($this->getThirdPartyProviders() as $provider) {
        $this->addDependency('module', $provider);
      }
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'edit-form', array $options = []) {
    return parent::urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function url($rel = 'edit-form', $options = array()) {
    return parent::url($rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function link($text = NULL, $rel = 'edit-form', array $options = []) {
    return parent::link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // Use cache tags that match the underlying config object's name.
    // @see \Drupal\Core\Config\ConfigBase::getCacheTags()
    return ['config:' . $this->getConfigDependencyName()];
  }

  /**
   * Overrides \Drupal\Core\Entity\DependencyTrait:addDependency().
   *
   * Note that this function should only be called from implementations of
   * \Drupal\Core\Config\Entity\ConfigEntityInterface::calculateDependencies(),
   * as dependencies are recalculated during every entity save.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityDependency::hasDependency()
   */
  protected function addDependency($type, $name) {
    // A config entity is always dependent on its provider. There is no need to
    // explicitly declare the dependency. An explicit dependency on Core, which
    // provides some plugins, is also not needed.
    if ($type == 'module' && ($name == $this->getEntityType()->getProvider() || $name == 'core')) {
      return $this;
    }

    return $this->addDependencyTrait($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->getEntityType()->getConfigPrefix() . '.' . $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTarget() {
    // For configuration entities, use the config ID for the config target
    // identifier. This ensures that default configuration (which does not yet
    // have UUIDs) can be provided and installed with references to the target,
    // and also makes config dependencies more readable.
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = FALSE;
    if (!empty($this->third_party_settings)) {
      $old_count = count($this->third_party_settings);
      $this->third_party_settings = array_diff_key($this->third_party_settings, array_flip($dependencies['module']));
      $changed = $old_count != count($this->third_party_settings);
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   *
   * Override to never invalidate the entity's cache tag; the config system
   * already invalidates it.
   */
  protected function invalidateTagsOnSave($update) {
    Cache::invalidateTags($this->getEntityType()->getListCacheTags());
  }

  /**
   * {@inheritdoc}
   *
   * Override to never invalidate the individual entities' cache tags; the
   * config system already invalidates them.
   */
  protected static function invalidateTagsOnDelete(EntityTypeInterface $entity_type, array $entities) {
    Cache::invalidateTags($entity_type->getListCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->third_party_settings[$module][$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    if (isset($this->third_party_settings[$module][$key])) {
      return $this->third_party_settings[$module][$key];
    }
    else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    return isset($this->third_party_settings[$module]) ? $this->third_party_settings[$module] : array();
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    unset($this->third_party_settings[$module][$key]);
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this module.
    if (empty($this->third_party_settings[$module])) {
      unset($this->third_party_settings[$module]);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    return array_keys($this->third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      if ($entity->isUninstalling() || $entity->isSyncing()) {
        // During extension uninstall and configuration synchronization
        // deletions are already managed.
        break;
      }
      // Fix or remove any dependencies.
      $config_entities = static::getConfigManager()->getConfigEntitiesToChangeOnDependencyRemoval('config', [$entity->getConfigDependencyName()], FALSE);
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $dependent_entity */
      foreach ($config_entities['update'] as $dependent_entity) {
        $dependent_entity->save();
      }
      foreach ($config_entities['delete'] as $dependent_entity) {
        $dependent_entity->delete();
      }
    }
  }

  /**
   * Gets the configuration manager.
   *
   * @return \Drupal\Core\Config\ConfigManager
   *   The configuration manager.
   */
  protected static function getConfigManager() {
    return \Drupal::service('config.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function isInstallable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function trustData() {
    $this->trustedData = TRUE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTrustedData() {
    return $this->trustedData;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();
    $this->trustedData = FALSE;
    return $return;
  }

}
