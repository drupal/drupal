<?Php

/**
 * @file
 * Contains \Drupal\config_override\EventSubscriber\ConfigModuleOverrideSubscriber.
 */

namespace Drupal\config_override\EventSubscriber;

use Drupal\Core\Config\ConfigModuleOverridesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigModuleOverrideSubscriber implements EventSubscriberInterface {

  public function onConfigModuleOverride(ConfigModuleOverridesEvent $event) {
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      $names = $event->getNames();
      if (in_array('system.site', $names)) {
        $event->setOverride('system.site', array('name' => 'ZOMG overridden site name'));
      }
      if (in_array('config_override.new', $names)) {
        $event->setOverride('config_override.new', array('module' => 'override'));
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
    $events['config.module.overrides'][] = array('onConfigModuleOverride', 40);
    return $events;
  }
}

