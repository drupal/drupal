<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityType.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\Entity\Exception\ConfigEntityStorageClassException;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Config\ConfigPrefixLengthException;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Provides an implementation of a configuration entity type and its metadata.
 */
class ConfigEntityType extends EntityType implements ConfigEntityTypeInterface {

  /**
   * The config prefix set in the configuration entity type annotation.
   *
   * The default configuration prefix is constructed from the name of the module
   * that provides the entity type and the ID of the entity type. If a
   * config_prefix annotation is present it will be used in place of the entity
   * type ID.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityType::getConfigPrefix()
   */
  protected $config_prefix;

  /**
   * {@inheritdoc}
   */
  protected $static_cache = FALSE;

  /**
   * The list of configuration entity properties to export from the annotation.
   *
   * @var array
   */
  protected $config_export = [];

  /**
   * The result of merging config_export annotation with the defaults.
   *
   * This is stored on the class so that it does not have to be recalculated.
   *
   * @var array
   */
  protected $mergedConfigExport = [];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\Entity\Exception\ConfigEntityStorageClassException
   *   Exception thrown when the provided class is not an instance of
   *   \Drupal\Core\Config\Entity\ConfigEntityStorage.
   */
  public function __construct($definition) {
    // Ensure a default list cache tag is set; do this before calling the parent
    // constructor, because we want "Configuration System style" cache tags.
    if (empty($this->list_cache_tags)) {
      $this->list_cache_tags = ['config:' . $definition['id'] . '_list'];
    }

    parent::__construct($definition);
    // Always add a default 'uuid' key.
    $this->entity_keys['uuid'] = 'uuid';
    $this->entity_keys['langcode'] = 'langcode';
    if (isset($this->handlers['storage'])) {
      $this->checkStorageClass($this->handlers['storage']);
    }
    $this->handlers += array(
      'storage' => 'Drupal\Core\Config\Entity\ConfigEntityStorage',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    // Ensure that all configuration entities are prefixed by the name of the
    // module that provides the configuration entity type. This ensures that
    // default configuration will be created as expected during module
    // installation and dependencies can be calculated without the modules that
    // provide the entity types being installed.
    if (isset($this->config_prefix)) {
      $config_prefix = $this->provider . '.' . $this->config_prefix;
    }
    else {
      $config_prefix = $this->provider . '.' . $this->id();
    }

    if (strlen($config_prefix) > static::PREFIX_LENGTH) {
      throw new ConfigPrefixLengthException(SafeMarkup::format('The configuration file name prefix @config_prefix exceeds the maximum character limit of @max_char.', array(
        '@config_prefix' => $config_prefix,
        '@max_char' => static::PREFIX_LENGTH,
      )));
    }
    return $config_prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDataTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencyKey() {
    return 'config';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Config\Entity\Exception\ConfigEntityStorageClassException
   *   Exception thrown when the provided class is not an instance of
   *   \Drupal\Core\Config\Entity\ConfigEntityStorage.
   */
  public function setStorageClass($class) {
    $this->checkStorageClass($class);
    parent::setStorageClass($class);
  }

  /**
   * Checks that the provided class is an instance of ConfigEntityStorage.
   *
   * @param string $class
   *   The class to check.
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityStorage.
   */
  protected function checkStorageClass($class) {
    if (!is_a($class, 'Drupal\Core\Config\Entity\ConfigEntityStorage', TRUE)) {
      throw new ConfigEntityStorageClassException(SafeMarkup::format('@class is not \Drupal\Core\Config\Entity\ConfigEntityStorage or it does not extend it', ['@class' => $class]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertiesToExport() {
    if (!empty($this->config_export)) {
      if (empty($this->mergedConfigExport)) {
        // Always add default properties to be exported.
        $this->mergedConfigExport = [
          'uuid' => 'uuid',
          'langcode' => 'langcode',
          'status' => 'status',
          'dependencies' => 'dependencies',
          'third_party_settings' => 'third_party_settings',
        ];
        foreach ($this->config_export as $property => $name) {
          if (is_numeric($property)) {
            $this->mergedConfigExport[$name] = $name;
          }
          else {
            $this->mergedConfigExport[$property] = $name;
          }
        }
      }
      return $this->mergedConfigExport;
    }
    return NULL;
  }

}
