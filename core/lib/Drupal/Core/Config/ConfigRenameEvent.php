<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigRenameEvent.
 */

namespace Drupal\Core\Config;

/**
 * Configuration event fired when renaming a configuration object.
 */
class ConfigRenameEvent extends ConfigCrudEvent {

  /**
   * The old configuration object name.
   *
   * @var string
   */
  protected $oldName;

  /**
   * Constructs the config rename event.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The configuration that has been renamed.
   * @param string $old_name
   *   The old configuration object name.
   */
  public function __construct(Config $config, $old_name) {
    $this->config = $config;
    $this->oldName = $old_name;
  }

  /**
   * Gets the old configuration object name.
   *
   * @return string
   *   The old configuration object name.
   */
  public function getOldName() {
    return $this->oldName;
  }

}
