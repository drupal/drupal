<?php

namespace Drupal\module_install_class_loader_test1;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber that does different things depending on whether classes
 * exist.
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
