<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ImmutableConfig.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Utility\String;

/**
 * Defines the immutable configuration object.
 *
 * Encapsulates all capabilities needed for runtime configuration handling
 * except being able to change the configuration.
 *
 * If you need to be able to change configuration use
 * \Drupal\Core\Form\ConfigFormBaseTrait or
 * \Drupal\Core\Config\ConfigFactoryInterface::getEditable().
 *
 * @see \Drupal\Core\Form\ConfigFormBaseTrait
 * @see \Drupal\Core\Config\ConfigFactoryInterface::getEditable()
 * @see \Drupal\Core\Config\ConfigFactoryInterface::get()
 *
 * @ingroup config_api
 */
class ImmutableConfig extends Config {

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    throw new ImmutableConfigException(String::format('Can not set values on immutable configuration !name:!key. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object', ['!name' => $this->getName(), '!key' => $key]));
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    throw new ImmutableConfigException(String::format('Can not clear !key key in immutable configuration !name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object', ['!name' => $this->getName(), '!key' => $key]));
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    throw new ImmutableConfigException(String::format('Can not save immutable configuration !name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object', ['!name' => $this->getName()]));
  }

  /**
   * Deletes the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function delete() {
    throw new ImmutableConfigException(String::format('Can not delete immutable configuration !name. Use \Drupal\Core\Config\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object', ['!name' => $this->getName()]));
  }

}
