<?Php

/**
 * @file
 * Contains \Drupal\config_override\EventSubscriber\ConfigModuleLowPriorityOverrideSubscriber.
 */

namespace Drupal\config_override\EventSubscriber;

use Drupal\Core\Config\ConfigModuleOverridesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests module overrides for configuration.
 */
class ConfigModuleLowPriorityOverrideSubscriber implements EventSubscriberInterface {

  public function onConfigModuleOverride(ConfigModuleOverridesEvent $event) {
    if (!empty($GLOBALS['config_test_run_module_overrides'])) {
      $names = $event->getNames();
      if (in_array('system.site', $names)) {
        $event->setOverride('system.site', array(
          'name' => 'Should not apply because of higher priority listener',
          // This override should apply because it is not overridden by the
          // higher priority listener.
          'slogan' => 'Yay for overrides!',
        ));
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
    $events['config.module.overrides'][] = array('onConfigModuleOverride', 35);
    return $events;
  }
}

