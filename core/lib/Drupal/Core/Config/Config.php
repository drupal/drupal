<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the default configuration object.
 *
 * Encapsulates all capabilities needed for configuration handling for a
 * specific configuration object, including support for runtime overrides. The
 * overrides are handled on top of the stored configuration so they are not
 * saved back to storage.
 *
 * @ingroup config_api
 */
class Config extends StorableConfigBase {

  /**
   * An event dispatcher instance to use for configuration events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The current runtime data.
   *
   * The configuration data from storage merged with module and settings
   * overrides.
   *
   * @var array
   */
  protected $overriddenData;

  /**
   * The current module overrides.
   *
   * @var array
   */
  protected $moduleOverrides;

  /**
   * The current settings overrides.
   *
   * @var array
   */
  protected $settingsOverrides;

  /**
   * Constructs a configuration object.
   *
   * @param string $name
   *   The name of the configuration object being constructed.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A storage object to use for reading and writing the
   *   configuration data.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   An event dispatcher instance to use for configuration events.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager service.
   */
  public function __construct($name, StorageInterface $storage, EventDispatcherInterface $event_dispatcher, TypedConfigManagerInterface $typed_config) {
    $this->name = $name;
    $this->storage = $storage;
    $this->eventDispatcher = $event_dispatcher;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function initWithData(array $data) {
    parent::initWithData($data);
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key = '') {
    if (!isset($this->overriddenData)) {
      $this->setOverriddenData();
    }
    if (empty($key)) {
      return $this->overriddenData;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return $this->overriddenData[$key] ?? NULL;
      }
      else {
        $value = NestedArray::getValue($this->overriddenData, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    parent::setData($data);
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets settings.php overrides for this configuration object.
   *
   * The overridden data only applies to this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return $this
   *   The configuration object.
   */
  public function setSettingsOverride(array $data) {
    $this->settingsOverrides = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets module overrides for this configuration object.
   *
   * @param array $data
   *   The overridden values of the configuration data.
   *
   * @return $this
   *   The configuration object.
   */
  public function setModuleOverride(array $data) {
    $this->moduleOverrides = $data;
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Sets the current data for this configuration object.
   *
   * Configuration overrides operate at two distinct layers: modules and
   * settings.php. Overrides in settings.php take precedence over values
   * provided by modules. Precedence or different module overrides is
   * determined by the priority of the config.factory.override tagged services.
   *
   * @return $this
   *   The configuration object.
   */
  protected function setOverriddenData() {
    $this->overriddenData = $this->data;
    if (isset($this->moduleOverrides) && is_array($this->moduleOverrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray([$this->overriddenData, $this->moduleOverrides], TRUE);
    }
    if (isset($this->settingsOverrides) && is_array($this->settingsOverrides)) {
      $this->overriddenData = NestedArray::mergeDeepArray([$this->overriddenData, $this->settingsOverrides], TRUE);
    }
    return $this;
  }

  /**
   * Resets the current data, so overrides are re-applied.
   *
   * This method should be called after the original data or the overridden data
   * has been changed.
   *
   * @return $this
   *   The configuration object.
   */
  protected function resetOverriddenData() {
    unset($this->overriddenData);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    parent::set($key, $value);
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    parent::clear($key);
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    // Validate the configuration object name before saving.
    static::validateName($this->name);

    // If there is a schema for this configuration object, cast all values to
    // conform to the schema.
    if (!$has_trusted_data) {
      if ($this->typedConfigManager->hasConfigSchema($this->name)) {
        // Ensure that the schema wrapper has the latest data.
        $this->schemaWrapper = NULL;
        $this->data = $this->castValue(NULL, $this->data);
        // Reclaim the memory used by the schema wrapper.
        $this->schemaWrapper = NULL;
      }
      else {
        foreach ($this->data as $key => $value) {
          $this->validateValue($key, $value);
        }
      }
    }

    // Potentially configuration schema could have changed the underlying data's
    // types.
    $this->resetOverriddenData();

    $this->storage->write($this->name, $this->data);
    if (!$this->isNew) {
      Cache::invalidateTags($this->getCacheTags());
    }
    $this->isNew = FALSE;
    $event_name = $this->getStorage()->getCollectionName() === StorageInterface::DEFAULT_COLLECTION ? ConfigEvents::SAVE : ConfigCollectionEvents::SAVE_IN_COLLECTION;
    $this->eventDispatcher->dispatch(new ConfigCrudEvent($this), $event_name);
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Deletes the configuration object.
   *
   * @return $this
   *   The configuration object.
   */
  public function delete() {
    $this->data = [];
    $this->storage->delete($this->name);
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = TRUE;
    $this->resetOverriddenData();
    $event_name = $this->getStorage()->getCollectionName() === StorageInterface::DEFAULT_COLLECTION ? ConfigEvents::DELETE : ConfigCollectionEvents::DELETE_IN_COLLECTION;
    $this->eventDispatcher->dispatch(new ConfigCrudEvent($this), $event_name);
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Gets original data from this configuration object.
   *
   * Original data is the data as it is immediately after loading from
   * configuration storage before any changes. If this is a new configuration
   * object it will be an empty array.
   *
   * @see \Drupal\Core\Config\Config::get()
   *
   * @param string $key
   *   A string that maps to a key within the configuration data.
   * @param bool $apply_overrides
   *   Apply any overrides to the original data. Defaults to TRUE.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function getOriginal($key = '', $apply_overrides = TRUE) {
    $original_data = $this->originalData;
    if ($apply_overrides) {
      // Apply overrides.
      if (isset($this->moduleOverrides) && is_array($this->moduleOverrides)) {
        $original_data = NestedArray::mergeDeepArray([$original_data, $this->moduleOverrides], TRUE);
      }
      if (isset($this->settingsOverrides) && is_array($this->settingsOverrides)) {
        $original_data = NestedArray::mergeDeepArray([$original_data, $this->settingsOverrides], TRUE);
      }
    }

    if (empty($key)) {
      return $original_data;
    }
    else {
      $parts = explode('.', $key);
      if (count($parts) == 1) {
        return $original_data[$key] ?? NULL;
      }
      else {
        $value = NestedArray::getValue($original_data, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

  /**
   * Determines if overrides are applied to a key for this configuration object.
   *
   * @param string $key
   *   (optional) A string that maps to a key within the configuration data.
   *   For instance in the following configuration array:
   *   @code
   *   [
   *     'foo' => [
   *       'bar' => 'baz',
   *     ],
   *   ];
   *   @endcode
   *   A key of 'foo.bar' would map to the string 'baz'. However, a key of 'foo'
   *   would map to the ['bar' => 'baz'].
   *   If not supplied TRUE will be returned if there are any overrides at all
   *   for this configuration object.
   *
   * @return bool
   *   TRUE if there are any overrides for the key, otherwise FALSE.
   */
  public function hasOverrides($key = '') {
    if (empty($key)) {
      return !(empty($this->moduleOverrides) && empty($this->settingsOverrides));
    }
    else {
      $parts = explode('.', $key);
      $override_exists = FALSE;
      if (isset($this->moduleOverrides) && is_array($this->moduleOverrides)) {
        $override_exists = NestedArray::keyExists($this->moduleOverrides, $parts);
      }
      if (!$override_exists && isset($this->settingsOverrides) && is_array($this->settingsOverrides)) {
        $override_exists = NestedArray::keyExists($this->settingsOverrides, $parts);
      }
      return $override_exists;
    }
  }

}
