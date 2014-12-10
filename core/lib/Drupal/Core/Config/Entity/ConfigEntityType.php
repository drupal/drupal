<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityType.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityType;
use Drupal\Core\Config\ConfigPrefixLengthException;
use Drupal\Component\Utility\String;

/**
 * Provides an implementation of a configuration entity type and its metadata.
 */
class ConfigEntityType extends EntityType {

  /**
   * Length limit of the configuration entity prefix.
   *
   * Configuration entity names are composed of two parts:
   * - The config prefix, which is returned by getConfigPrefix() and is
   *   composed of:
   *   - The provider module name (limited to 50 characters by
   *     DRUPAL_EXTENSION_NAME_MAX_LENGTH).
   *   - The module-specific namespace identifier, which defaults to the
   *     configuration entity type ID. Entity type IDs are limited to 32
   *     characters by EntityTypeInterface::ID_MAX_LENGTH.
   * - The configuration entity ID.
   * So, a typical configuration entity filename will look something like:
   * provider_module_name.namespace_identifier.config_entity_id.yml
   *
   * Most file systems limit a file name's length to 255 characters, so
   * ConfigBase::MAX_NAME_LENGTH restricts the full configuration object name
   * to 250 characters (leaving 5 for the file extension). Therefore, in
   * order to leave sufficient characters to construct a configuration ID,
   * the configuration entity prefix is limited to 83 characters: up to 50
   * characters for the module name, 1 for the dot, and 32 for the namespace
   * identifier. This also allows modules with shorter names to define longer
   * namespace identifiers if desired.
   *
   * @see \Drupal\Core\Config\ConfigBase::MAX_NAME_LENGTH
   * @see \Drupal\Core\Config\Entity\ConfigEntityTypeInterface::getConfigPrefix()
   * @see DRUPAL_EXTENSION_NAME_MAX_LENGTH
   * @see \Drupal\Core\Config\Entity\ConfigEntityStorage::MAX_ID_LENGTH
   * @see \Drupal\Core\Entity\EntityTypeInterface::ID_MAX_LENGTH
   */
  const PREFIX_LENGTH = 83;

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @var string
   */
  protected $config_prefix;

  /**
   * {@inheritdoc}
   */
  protected $static_cache = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition) {
    parent::__construct($definition);
    // Always add a default 'uuid' key.
    $this->entity_keys['uuid'] = 'uuid';
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
      throw new ConfigPrefixLengthException(String::format('The configuration file name prefix @config_prefix exceeds the maximum character limit of @max_char.', array(
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

}
