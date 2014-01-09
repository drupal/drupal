<?php
/**
 * @file
 * Contains \Drupal\system\SystemConfigSubscriber.
 */

namespace Drupal\system;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Core\Config\StorageDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System Config subscriber.
 */
class SystemConfigSubscriber implements EventSubscriberInterface {

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.importer.validate'][] = array('onConfigImporterValidate', 20);
    return $events;
  }

  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    $importer = $event->getConfigImporter();
    $importList = $importer->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importerList)) {
      throw new ConfigImporterException("This import will delete all your active configuration, I'm bailing out now.");
    }
  }
}

