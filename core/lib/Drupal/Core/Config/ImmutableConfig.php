<?php

namespace Drupal\Core\Config;

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
    throw new ImmutableConfigException("Can not set values on immutable configuration {$this->getName()}:$key. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * {@inheritdoc}
   */
  public function clear($key) {
    throw new ImmutableConfigException("Can not clear $key key in immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * {@inheritdoc}
   */
  public function save($has_trusted_data = FALSE) {
    throw new ImmutableConfigException("Can not save immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

  /**
   * Deletes the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object.
   */
  public function delete() {
    throw new ImmutableConfigException("Can not delete immutable configuration {$this->getName()}. Use \\Drupal\\Core\\Config\\ConfigFactoryInterface::getEditable() to retrieve a mutable configuration object");
  }

}
