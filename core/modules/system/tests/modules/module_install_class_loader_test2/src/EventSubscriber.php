<?php

namespace Drupal\module_install_class_loader_test2;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber that does nothing.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [];
  }

}
