<?php

/**
 * @file
 * Contains \Drupal\system\SystemConfigSubscriber.
 */

namespace Drupal\system;

use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * System Config subscriber.
 */
class SystemConfigSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener prevents deleting all configuration. If there is
   * nothing to import then event propagation is stopped because there is no
   * config import to validate.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateNotEmpty(ConfigImporterEvent $event) {
    $importList = $event->getConfigImporter()->getStorageComparer()->getSourceStorage()->listAll();
    if (empty($importList)) {
      $event->getConfigImporter()->logError($this->t('This import is empty and if applied would delete all of your configuration, so has been rejected.'));
      $event->stopPropagation();
    }
  }

  /**
   * Checks that the configuration synchronization is valid.
   *
   * This event listener checks that the system.site:uuid's in the source and
   * target match.
   *
   * @param ConfigImporterEvent $event
   *   The config import event.
   */
  public function onConfigImporterValidateSiteUUID(ConfigImporterEvent $event) {
    if (!$event->getConfigImporter()->getStorageComparer()->validateSiteUuid()) {
      $event->getConfigImporter()->logError($this->t('Site UUID in source storage does not match the target storage.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The empty check has a high priority so that is can stop propagation if
    // there is no configuration to import.
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidateNotEmpty', 512);
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidateSiteUUID', 256);
    return $events;
  }
}
