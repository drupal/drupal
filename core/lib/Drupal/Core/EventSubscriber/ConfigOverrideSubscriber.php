<?php
/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\ConfigOverrideSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigEvent;
use Drupal\Core\Config\Context\ConfigContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Override configuration values with predefined values in context.
 */
class ConfigOverrideSubscriber implements EventSubscriberInterface {

  /**
   * Overrides configuration values.
   *
   * @param \Drupal\Core\Config\ConfigEvent $event
   *   The Event to process.
   */
  public function configInit(ConfigEvent $event) {
    if ($override = $event->getContext()->get(ConfigContext::OVERRIDE)) {
      $config = $event->getConfig();
      if (isset($override[$config->getName()])) {
        $config->setOverride($override[$config->getName()]);
      }
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    $events['config.init'][] = array('configInit', 30);
    return $events;
  }
}
