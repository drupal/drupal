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
   * Most file systems limit a file name's length to 255 characters. In
   * order to leave sufficient characters to construct a configuration ID,
   * the configuration entity prefix is limited to 83 characters which
   * leaves 166 characters for the configuration ID. 5 characters are
   * reserved for the file extension.
   *
   * @see \Drupal\Core\Config\ConfigBase::MAX_NAME_LENGTH
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
  public function getControllerClasses() {
    return parent::getControllerClasses() + array(
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
  public function getKeys() {
    // Always add a default 'uuid' key.
    return array('uuid' => 'uuid') + parent::getKeys();
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

}
