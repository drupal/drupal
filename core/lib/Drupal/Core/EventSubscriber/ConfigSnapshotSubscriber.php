<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Create a snapshot when config is imported.
 */
class ConfigSnapshotSubscriber implements EventSubscriberInterface {

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

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
  public function __construct(ConfigManagerInterface $config_manager, StorageInterface $source_storage, StorageInterface $snapshot_storage) {
    $this->configManager = $config_manager;
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
    $this->configManager->createSnapshot($this->sourceStorage, $this->snapshotStorage);
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT][] = ['onConfigImporterImport', 40];
    return $events;
  }

}
