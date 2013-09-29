<?php

/**
 * @file
 * Contains \Drupal\devel\EventSubscriber\DevelEventSubscriber.
 */

namespace Drupal\devel\EventSubscriber;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Database;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class DevelEventSubscriber implements EventSubscriberInterface {

  /**
   * The devel.settings config object.
   *
   * @var \Drupal\Core\Config\Config;
   */
  protected $config;

  /**
   * Constructs a DevelEventSubscriber object.
   */
  public function __construct(ConfigFactory $factory) {
    $this->config = $factory->get('devel.settings');
  }

  /**
   * Initializes devel module requirements.
   */
  public function onRequest(GetResponseEvent $event) {
    if (!devel_silent()) {
      if ($this->config->get('memory')) {
        global $memory_init;
        $memory_init = memory_get_usage();
      }

      if (devel_query_enabled()) {
        Database::startLog('devel');
      }
    }

    // Initialize XHProf.
    devel_xhprof_enable();

    // We need user_access() in the shutdown function. make sure it gets loaded.
    // Also prime the drupal_get_filename() static with user.module's location to
    // avoid a stray query.
    drupal_get_filename('module', 'user', 'core/modules/user/user.module');
    drupal_load('module', 'user');
    drupal_register_shutdown_function('devel_shutdown');
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    // Set a low value to start as early as possible.
    $events[KernelEvents::REQUEST][] = array('onRequest', -100);

    return $events;
  }

}
