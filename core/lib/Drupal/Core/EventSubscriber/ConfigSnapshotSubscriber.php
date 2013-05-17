<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ConfigSnapshotSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a snapshot when config is imported.
 */
class ConfigSnapshotSubscriber implements EventSubscriberInterface {

  /**
   * The source storage used to discover configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sourceStorage;

  /**
   * The snapshot storage used to write configuration changes.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $snapshotStorage;

  /**
   * Constructs the ConfigSnapshotSubscriber object.
   *
   * @param StorageInterface $source_storage
   *   The source storage used to discover configuration changes.
   * @param StorageInterface $snapshot_storage
   *   The snapshot storage used to write configuration changes.
   */
  public function __construct(StorageInterface $source_storage, StorageInterface $snapshot_storage) {
    $this->sourceStorage = $source_storage;
    $this->snapshotStorage = $snapshot_storage;
  }

  /**
   * Creates a config snapshot.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The Event to process.
   */
  public function onConfigImporterImport(ConfigImporterEvent $event) {
    config_import_create_snapshot($this->sourceStorage, $this->snapshotStorage);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events['config.importer.import'][] = array('onConfigImporterImport', 40);
    return $events;
  }

}
