<?php

/**
 * @file
 * Contains \Drupal\system\SystemConfigSubscriber.
 */

namespace Drupal\system;

use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;
use Drupal\Core\Config\StorageDispatcher;

/**
 * System Config subscriber.
 */
class SystemConfigSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener implements two checks:
   *   - prevents deleting all configuration.
   *   - checks that the system.site:uuid's in the source and target match.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    $importList = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importList)) {
      $event->getConfigImporter()->logError($this->t('This import is empty and if applied would delete all of your configuration, so has been rejected.'));
    }
    if (!$event->getConfigImporter()->getStorageComparer()->validateSiteUuid()) {
      $event->getConfigImporter()->logError($this->t('Site UUID in source storage does not match the target storage.'));
    }
  }
}
