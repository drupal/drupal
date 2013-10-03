<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Context\ContextInterface.
 */

namespace Drupal\Core\Config\Context;

use Drupal\Core\Config\Config;

/**
 * Defines the configuration context interface.
 *
 * The configuration context object will contain predefined parameters used
 * by the configuration object for storage operations and notifications
 * and contextual data to be used by configuration event listeners.
 *
 * @see \Drupal\Core\Config\Config
 * @see \Drupal\Core\Config\ConfigFactory
 * @see \Drupal::config()
 */
interface ContextInterface {

  /**
   * Initializes a configuration context for use.
   *
   * @return \Drupal\Core\Config\Context\ConfigContext
   *   The config context object.
   */
  public function init();

  /**
   * Returns the stored value for a given key.
   *
   * @param string $key
   *   The key of the data to retrieve.
   *
   * @return mixed
   *   The stored value, or NULL if no value exists.
   */
  public function get($key);

  /**
   * Saves a value for a given key.
   *
   * @param string $key
   *   The key of the data to store.
   * @param mixed $value
   *   The data to store.
   */
  public function set($key, $value);

  /**
   * Sets the uuid for the context.
   *
   * @return string
   *   The context's uuid.
   */
  public function setUuid();

  /**
   * Gets the uuid for the context.
   *
   * @return string
   *   The context's uuid.
   */
  public function getUuid();

  /**
   * Dispatches a config event.
   *
   * @param string $config_event_name
   *   Event name.
   * @param \Drupal\Core\Config\Config $config
   *   (optional) Configuration object.
   */
  public function notify($config_event_name, Config $config = NULL);

  /**
   * Sets the override data for a configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   * @param array data
   *   The override data.
   */
  public function setOverrides($config_name, $data);

  /**
   * Gets the override data for a configuration object.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return mixed
   *   The override data or FALSE if there is none.
   */
  public function getOverrides($config_name);

}
