<?php

/**
 * @file
 * Contains \Drupal\menu_test\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\menu_test\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\EventSubscriber\MaintenanceModeSubscriber as CoreMaintenanceModeSubscriber;


/**
 * Maintenance mode subscriber to set site online on a test.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Set the page online if called from a certain path.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
    // Allow access to menu_login_callback even if in maintenance mode.
    if ($request->attributes->get('_maintenance') == CoreMaintenanceModeSubscriber::SITE_OFFLINE && $request->attributes->get('_system_path') == 'menu_login_callback') {
      $request->attributes->set('_maintenance', CoreMaintenanceModeSubscriber::SITE_ONLINE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 35);
    return $events;
  }

}
