<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigStorageControllerInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Provides an interface for configuration entity storage.
 */
interface ConfigStorageControllerInterface extends EntityStorageControllerInterface {

  /**
   * Create configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importCreate($name, Config $new_config, Config $old_config);

  /**
   * Updates configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importUpdate($name, Config $new_config, Config $old_config);

  /**
   * Delete configuration upon synchronizing configuration changes.
   *
   * This callback is invoked when configuration is synchronized between storages
   * and allows a module to take over the synchronization of configuration data.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param \Drupal\Core\Config\Config $new_config
   *   A configuration object containing the new configuration data.
   * @param \Drupal\Core\Config\Config $old_config
   *   A configuration object containing the old configuration data.
   */
  public function importDelete($name, Config $new_config, Config $old_config);

  /**
   * Returns the config prefix used by the configuration entity type.
   *
   * @return string
   *   The full configuration prefix, for example 'views.view.'.
   */
  public function getConfigPrefix();

  /**
   * Extracts the configuration entity ID from the full configuration name.
   *
   * @param string $config_name
   *   The full configuration name to extract the ID from. E.g.
   *   'views.view.archive'.
   * @param string $config_prefix
   *   The config prefix of the configuration entity. E.g. 'views.view'
   *
   * @return string
   *   The ID of the configuration entity.
   */
  public static function getIDFromConfigName($config_name, $config_prefix);

}
