<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ConfigImportSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\ConfigImporterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Config import subscriber for config import events.
 */
class ConfigImportSubscriber implements EventSubscriberInterface {

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
      foreach ($event->getConfigImporter()->getUnprocessed($op) as $name) {
        Config::validateName($name);
      }
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT_VALIDATE][] = array('onConfigImporterValidate', 40);
    return $events;
  }

}
