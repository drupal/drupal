<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ConfigImportSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigImportValidateEventSubscriberBase;
use Drupal\Core\Config\ConfigNameException;

/**
 * Config import subscriber for config import events.
 */
class ConfigImportSubscriber extends ConfigImportValidateEventSubscriberBase {

  /**
   * Validates the configuration to be imported.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The Event to process.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   */
  public function onConfigImporterValidate(ConfigImporterEvent $event) {
    foreach (array('delete', 'create', 'update') as $op) {
      foreach ($event->getConfigImporter()->getUnprocessedConfiguration($op) as $name) {
        try {
          Config::validateName($name);
        }
        catch (ConfigNameException $e) {
          $message = $this->t('The config name @config_name is invalid.', array('@config_name' => $name));
          $event->getConfigImporter()->logError($message);
        }
      }
    }
  }

}
