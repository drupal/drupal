<?php
/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\ConfigGlobalOverrideSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Override configuration values with values in global $conf variable.
 */
class ConfigGlobalOverrideSubscriber implements EventSubscriberInterface {
  /**
   * Override configuration values with global $conf.
   *
   * @param Drupal\Core\Config\ConfigEvent $event
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
