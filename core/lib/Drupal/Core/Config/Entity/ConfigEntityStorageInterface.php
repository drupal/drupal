<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityStorageInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Provides an interface for configuration entity storage.
 */
interface ConfigEntityStorageInterface extends EntityStorageInterface {

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
