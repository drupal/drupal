<?php

/**
 * @file
 * Definition of Drupal\Core\Config\Entity\ConfigEntityBase.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Config\ConfigDuplicateUUIDException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginBagsInterface;
use Drupal\Core\Language\Language;
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
   * This is needed when the entity utilizes a PluginBag, to dictate where the
   * plugin configuration should be stored.
   *
   * @var string
   */
  protected $pluginConfigKey;

  /**
   * The enabled/disabled status of the configuration entity.
   *
   * @var bool
   */
  public $status = TRUE;

  /**
   * The UUID for this entity.
   *
   * @var string
   */
  public $uuid;

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
   * @var string
   */
  public $langcode = Language::LANGCODE_NOT_SPECIFIED;

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
    $this->originalId = $id;

    return parent::setOriginalId($id);
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
    if ($this instanceof EntityWithPluginBagsInterface) {
      $plugin_bags = $this->getPluginBags();
      if (isset($plugin_bags[$property_name])) {
        // If external code updates the settings, pass it along to the plugin.
        $plugin_bags[$property_name]->setConfiguration($value);
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
    Cache::invalidateTags(array($this->entityTypeId => array($this->id())));
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
    // Configuration objects do not have a schema. Extract all key names from
    // class properties.
    $class_info = new \ReflectionClass($this);
    $properties = array();
    foreach ($class_info->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $properties[$name] = $this->get($name);
    }
    // Add protected dependencies property.
    $properties['dependencies'] = $this->dependencies;
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this instanceof EntityWithPluginBagsInterface) {
      // Any changes to the plugin configuration must be saved to the entity's
      // copy as well.
      foreach ($this->getPluginBags() as $plugin_config_key => $plugin_bag) {
        $this->set($plugin_config_key, $plugin_bag->getConfiguration());
      }
    }

    // Ensure this entity's UUID does not exist with a different ID, regardless
    // of whether it's new or updated.
    $matching_entities = $storage->getQuery()
      ->condition('uuid', $this->uuid())
      ->execute();
    $matched_entity = reset($matching_entities);
    if (!empty($matched_entity) && ($matched_entity != $this->id()) && $matched_entity != $this->getOriginalId()) {
      throw new ConfigDuplicateUUIDException(String::format('Attempt to save a configuration entity %id with UUID %uuid when this UUID is already used for %matched', array('%id' => $this->id(), '%uuid' => $this->uuid(), '%matched' => $matched_entity)));
    }

    // If this entity is not new, load the original entity for comparison.
    if (!$this->isNew()) {
      $original = $storage->loadUnchanged($this->getOriginalId());
      // Ensure that the UUID cannot be changed for an existing entity.
      if ($original && ($original->uuid() != $this->uuid())) {
        throw new ConfigDuplicateUUIDException(String::format('Attempt to save a configuration entity %id with UUID %uuid when this entity already exists with UUID %original_uuid', array('%id' => $this->id(), '%uuid' => $this->uuid(), '%original_uuid' => $original->uuid())));
      }
    }
    if (!$this->isSyncing()) {
      // Ensure the correct dependencies are present. If the configuration is
      // being written during a configuration synchronisation then there is no
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
    $this->dependencies = array();
    if ($this instanceof EntityWithPluginBagsInterface) {
      // Configuration entities need to depend on the providers of any plugins
      // that they store the configuration for.
      foreach ($this->getPluginBags() as $plugin_bag) {
        foreach ($plugin_bag as $instance) {
          $this->calculatePluginDependencies($instance);
        }
      }
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function urlInfo($rel = 'edit-form') {
    return parent::urlInfo($rel);
  }

  /**
   * {@inheritdoc}
   */
  public function getSystemPath($rel = 'edit-form') {
    return parent::getSystemPath($rel);
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
  protected function addDependency($type, $name) {
    // A config entity is always dependent on its provider. There is no need to
    // explicitly declare the dependency. An explicit dependency on Core, which
    // provides some plugins, is also not needed.
    // @see \Drupal\Core\Config\Entity\ConfigEntityDependency::hasDependency()
    if ($type == 'module' && ($name == $this->getEntityType()->getProvider() || $name == 'Core')) {
      return $this;
    }

    return $this->addDependencyTrait($type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyName() {
    return $this->getEntityType()->getConfigPrefix() . '.' . $this->id();
  }

}
