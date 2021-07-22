<?php

namespace Drupal\Core\Config;

use Drupal\Component\EventDispatcher\Event;

/**
 * Configuration event fired when importing a configuration object.
 */
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

  /**
   * Gets the list of changes that will be imported.
   *
   * @param string $op
   *   (optional) A change operation. Either delete, create or update. If
   *   supplied the returned list will be limited to this operation.
   * @param string $collection
   *   (optional) The collection to get the changelist for. Defaults to the
   *   default collection.
   *
   * @return array
   *   An array of config changes that are yet to be imported.
   *
   * @see \Drupal\Core\Config\StorageComparerInterface::getChangelist()
   */
  public function getChangelist($op = NULL, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return $this->configImporter->getStorageComparer()->getChangelist($op, $collection);
  }

}
