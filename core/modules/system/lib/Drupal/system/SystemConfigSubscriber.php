<?php

/**
 * @file
 * Contains \Drupal\system\SystemConfigSubscriber.
 */

namespace Drupal\system;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\StorageDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System Config subscriber.
 */
class SystemConfigSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidate', 20);
    return $events;
  }

  /**
   * Checks that the import source storage is not empty.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   *
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   Exception thrown if the source storage is empty.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    $importList = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importList)) {
      throw new ConfigImporterException('This import is empty and if applied would delete all of your configuration, so has been rejected.');
    }
  }
}
