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
   * Constructs the ConfigSnapshotSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   The configuration manager.
   * @param \Drupal\Core\Config\StorageInterface $sourceStorage
   *   The source storage used to discover configuration changes.
   * @param \Drupal\Core\Config\StorageInterface $snapshotStorage
   *   The snapshot storage used to write configuration changes.
   */
  public function __construct(
    protected ConfigManagerInterface $configManager,
    protected StorageInterface $sourceStorage,
    protected StorageInterface $snapshotStorage,
  ) {
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
  public static function getSubscribedEvents(): array {
    $events[ConfigEvents::IMPORT][] = ['onConfigImporterImport', 40];
    return $events;
  }

}
