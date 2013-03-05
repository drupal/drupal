<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines a configuration global override for contexts.
 */
class ConfigGlobalOverrideSubscriber implements EventSubscriberInterface {

  /**
   * Overrides configuration values with values in global $conf variable.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configInit(ConfigEvent $event) {
    global $conf;

    $config = $event->getConfig();
    if (isset($conf[$config->getName()])) {
      $config->setOverride($conf[$config->getName()]);
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events['config.init'][] = array('configInit', 30);
    return $events;
  }

}
