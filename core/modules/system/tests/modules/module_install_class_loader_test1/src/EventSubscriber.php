<?php

declare(strict_types=1);

namespace Drupal\module_install_class_loader_test1;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines an event subscriber that conditionally unsets the event list.
 *
 * @see Drupal\module_install_class_loader_test2\EventSubscriber
 * @see Drupal\Tests\system\Functional\Module\ClassLoaderTest::testMultipleModules()
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    // If the autoloader is not fixed during module install when the modules
    // module_install_class_loader_test1 and module_install_class_loader_test2
    // are enabled in the same request the class_exists() will cause a crash.
    // This is because \Composer\Autoload\ClassLoader maintains a negative
    // cache.
    if (class_exists('\Drupal\module_install_class_loader_test2\EventSubscriber')) {
      $events = [];
    }
    return $events;
  }

}
