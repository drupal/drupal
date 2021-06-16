<?php

namespace Drupal\config;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;

/**
 * Config subscriber.
 */
class ConfigSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * Checks that the Configuration module is not being uninstalled.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    // Make sure config syncs performed via the Config UI don't break, but
    // don't worry about syncs initiated via the command line.
    if (PHP_SAPI === 'cli') {
      return;
    }
    $importer = $event->getConfigImporter();
    $core_extension = $importer->getStorageComparer()->getSourceStorage()->read('core.extension');
    if (!isset($core_extension['module']['config'])) {
      $importer->logError($this->t('Can not uninstall the Configuration module as part of a configuration synchronization through the user interface.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = ['onConfigImporterValidate', 20];
    return $events;
  }

}
