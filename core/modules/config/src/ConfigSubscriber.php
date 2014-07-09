<?php

/**
 * @file
 * Contains \Drupal\config\ConfigSubscriber.
 */

namespace Drupal\config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;


/**
 * Config subscriber.
 */
class ConfigSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * Checks that the Configuration module is not being uninstalled.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    $importer = $event->getConfigImporter();
    $core_extension = $importer->getStorageComparer()->getSourceStorage()->read('core.extension');
    if (!isset($core_extension['module']['config'])) {
      $importer->logError($this->t('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidate', 20);
    return $events;
  }
}
