<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigImporterEvent.
 */

namespace Drupal\Core\Config;

use Symfony\Component\EventDispatcher\Event;

class ConfigImporterEvent extends Event {
  /**
   * Configuration import object.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Constructs ConfigImporterEvent.
   *
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   A config import object to notify listeners about.
   */
  public function __construct(ConfigImporter $config_importer) {
    $this->configImporter = $config_importer;
  }

  /**
   * Gets the config import object.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *   The ConfigImporter object.
   */
  public function getConfigImporter() {
    return $this->configImporter;
  }

}
