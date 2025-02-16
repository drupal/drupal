<?php

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\Entity\Exception\ConfigEntityStorageClassException;
use Drupal\Core\Entity\EntityType;
use Drupal\Core\Config\ConfigPrefixLengthException;

/**
 * Provides an implementation of a configuration entity type and its metadata.
 */
class ConfigEntityType extends EntityType implements ConfigEntityTypeInterface {

  /**
   * The config prefix set in the configuration entity type annotation.
   *
   * @var string
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityTypeInterface::getConfigPrefix()
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected $config_prefix;

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected $static_cache = FALSE;

  /**
   * Keys that are stored key value store for fast lookup.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
  protected $lookup_keys = [];

  /**
   * The list of configuration entity properties to export from the annotation.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName, Drupal.Commenting.VariableComment.Missing
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
    if (empty($definition['list_cache_tags'])) {
      $definition['list_cache_tags'] = ['config:' . $definition['id'] . '_list'];
    }

    parent::__construct($definition);
    // Always add a default 'uuid' key.
    $this->entity_keys['uuid'] = 'uuid';
    $this->entity_keys['langcode'] = 'langcode';
    $this->handlers += [
      'storage' => 'Drupal\Core\Config\Entity\ConfigEntityStorage',
    ];
    $this->lookup_keys[] = 'uuid';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigPrefix() {
    // Ensure that all configuration entities are prefixed by the name of the
    // module that provides the configuration entity type.
    if (isset($this->config_prefix)) {
      $config_prefix = $this->provider . '.' . $this->config_prefix;
    }
    else {
      $config_prefix = $this->provider . '.' . $this->id();
    }

    if (strlen($config_prefix) > static::PREFIX_LENGTH) {
      throw new ConfigPrefixLengthException("The configuration file name prefix $config_prefix exceeds the maximum character limit of " . static::PREFIX_LENGTH);
    }
    return $config_prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseTable() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionDataTable() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionTable() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataTable() {
    return NULL;
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
   *
   * @see \Drupal\Core\Config\Entity\ConfigEntityStorage
   */
  protected function checkStorageClass($class) {
    if (!is_a($class, 'Drupal\Core\Config\Entity\ConfigEntityStorage', TRUE)) {
      throw new ConfigEntityStorageClassException("$class is not \\Drupal\\Core\\Config\\Entity\\ConfigEntityStorage or it does not extend it");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertiesToExport($id = NULL) {
    // @todo https://www.drupal.org/project/drupal/issues/3113620 Make the
    //   config_export annotation required earlier, remove the possibility of
    //   returning NULL and deprecate the $id argument.
    if (!empty($this->mergedConfigExport)) {
      return $this->mergedConfigExport;
    }
    if (!empty($this->config_export)) {
      // Always add default properties to be exported.
      $this->mergedConfigExport = [
        'uuid' => 'uuid',
        'langcode' => 'langcode',
        'status' => 'status',
        'dependencies' => 'dependencies',
        'third_party_settings' => 'third_party_settings',
        '_core' => '_core',
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
    else {
      return NULL;
    }
    return $this->mergedConfigExport;
  }

  /**
   * {@inheritdoc}
   */
  public function getLookupKeys() {
    return $this->lookup_keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // If there is an ID key for this config entity type, make it immutable by
    // default. Individual config entities can override this with an
    // `ImmutableProperties` constraint in their definition that is either
    // empty, or with an alternative set of immutable properties.
    $id_key = $this->getKey('id');
    if ($id_key) {
      $constraints += [
        'ImmutableProperties' => [$id_key],
      ];
    }
    return $constraints;
  }

}
