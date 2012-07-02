<?php

/**
 * @file
 * Definition of Drupal\Core\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maintenance mode subscriber for controller requests.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Response with the maintenance page when the site is offline.
   *
   * @param Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestMaintenanceModeCheck(GetResponseEvent $event) {
    // Check if the site is offline.
    $status = _menu_site_is_offline() ? MENU_SITE_OFFLINE : MENU_SITE_ONLINE;

    // Allow other modules to change the site status but not the path because
    // that would not change the global variable. hook_url_inbound_alter() can
    // be used to change the path. Code later will not use the $read_only_path
    // variable.
    $read_only_path = !empty($path) ? $path : $event->getRequest()->attributes->get('system_path');
    drupal_alter('menu_site_status', $status, $read_only_path);

    // Only continue if the site is online.
    if ($status != MENU_SITE_ONLINE) {
      // Deliver the 503 page.
      drupal_maintenance_theme();
      drupal_set_title(t('Site under maintenance'));
      $content = theme('maintenance_page', array('content' => filter_xss_admin(variable_get('maintenance_mode_message', t('@site is currently under maintenance. We should be back shortly. Thank you for your patience.', array('@site' => config('system.site')->get('name')))))));
      $response = new Response('Service unavailable', 503);
      $response->setContent($content);
      $event->setResponse($response);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenanceModeCheck', 40);
    return $events;
  }
}
