<?php

/**
 * @file
 * Contains \Drupal\language\EventSubscriber\ConfigSubscriber.
 */

namespace Drupal\language\EventSubscriber;

use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Deletes the container if default language has changed.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * Causes the container to be rebuilt on the next request.
   *
   * @param ConfigCrudEvent $event
   *   The configuration event.
   */
  public function onConfigSave(ConfigCrudEvent $event) {
    $saved_config = $event->getConfig();
    if ($saved_config->getName() == 'system.site' && $event->isChanged('langcode')) {
      // Trigger a container rebuild on the next request by deleting compiled
      // from PHP storage.
      PhpStorageFactory::get('service_container')->deleteAll();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[ConfigEvents::SAVE][] = array('onConfigSave', 0);
    return $events;
  }

}
