<?php

namespace Drupal\Core\Config;

/**
 * Configuration event fired when renaming a configuration object.
 */
class ConfigRenameEvent extends ConfigCrudEvent {

  /**
   * Constructs the config rename event.
   *
   * @param \Drupal\Core\Config\StorableConfigBase $config
   *   The configuration that has been renamed.
   * @param string $oldName
   *   The old configuration object name.
   */
  public function __construct(
    StorableConfigBase $config,
    protected $oldName,
  ) {
    $this->config = $config;
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
