<?php

namespace Drupal\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
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
        return isset($this->overriddenData[$key]) ? $this->overriddenData[$key] : NULL;
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
   * @return \Drupal\Core\Config\Config
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
   * @return \Drupal\Core\Config\Config
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
   * @return \Drupal\Core\Config\Config
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
   * @return \Drupal\Core\Config\Config
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
        foreach ($this->data as $key => $value) {
          $this->data[$key] = $this->castValue($key, $value);
        }
      }
      else {
        foreach ($this->data as $key => $value) {
          $this->validateValue($key, $value);
        }
      }
    }

    $this->storage->write($this->name, $this->data);
    if (!$this->isNew) {
      Cache::invalidateTags($this->getCacheTags());
    }
    $this->isNew = FALSE;
    $this->eventDispatcher->dispatch(ConfigEvents::SAVE, new ConfigCrudEvent($this));
    $this->originalData = $this->data;
    // Potentially configuration schema could have changed the underlying data's
    // types.
    $this->resetOverriddenData();
    return $this;
  }

  /**
   * Deletes the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function delete() {
    $this->data = [];
    $this->storage->delete($this->name);
    Cache::invalidateTags($this->getCacheTags());
    $this->isNew = TRUE;
    $this->resetOverriddenData();
    $this->eventDispatcher->dispatch(ConfigEvents::DELETE, new ConfigCrudEvent($this));
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * Gets the raw data without overrides.
   *
   * @return array
   *   The raw data.
   */
  public function getRawData() {
    return $this->data;
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
        return isset($original_data[$key]) ? $original_data[$key] : NULL;
      }
      else {
        $value = NestedArray::getValue($original_data, $parts, $key_exists);
        return $key_exists ? $value : NULL;
      }
    }
  }

}
