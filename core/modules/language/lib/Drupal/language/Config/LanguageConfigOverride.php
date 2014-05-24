<?php

/**
 * @file
 * Contains \Drupal\language\Config\LanguageConfigOverride.
 */

namespace Drupal\language\Config;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Defines language configuration overrides.
 */
class LanguageConfigOverride extends StorableConfigBase {

  /**
   * Constructs a language override object.
   *
   * @param string $name
   *   The name of the configuration object being overridden.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A storage controller object to use for reading and writing the
   *   configuration override.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager service.
   */
  public function __construct($name, StorageInterface $storage, TypedConfigManagerInterface $typed_config) {
    $this->name = $name;
    $this->storage = $storage;
    $this->typedConfigManager = $typed_config;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // @todo Use configuration schema to validate.
    //   https://drupal.org/node/2270399
    // Perform basic data validation.
    foreach ($this->data as $key => $value) {
      $this->validateValue($key, $value);
    }
    $this->storage->write($this->name, $this->data);
    $this->isNew = FALSE;
    $this->originalData = $this->data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $this->data = array();
    $this->storage->delete($this->name);
    $this->isNew = TRUE;
    $this->originalData = $this->data;
    return $this;
  }

}
