<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Maintenance mode subscriber for controller requests.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Determine whether the page is configured to be offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestDetermineSiteStatus(GetResponseEvent $event) {
    // Check if the site is offline.
    $request = $event->getRequest();
    $is_offline = _menu_site_is_offline() ? MENU_SITE_OFFLINE : MENU_SITE_ONLINE;
    $request->attributes->set('_maintenance', $is_offline);
  }

  /**
   * Returns the site maintenance page if the site is offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
    $response = $event->getResponse();
    // Continue if the site is online and the response is not a redirection.
    if ($request->attributes->get('_maintenance') != MENU_SITE_ONLINE && !($response instanceof RedirectResponse)) {
      // Deliver the 503 page.
      drupal_maintenance_theme();
      $maintenance_page = array(
        '#theme' => 'maintenance_page',
        '#title' => t('Site under maintenance'),
        '#content' => filter_xss_admin(
          t(\Drupal::config('system.maintenance')->get('message'), array('@site' => \Drupal::config('system.site')->get('name')))
        ),
      );
      $content = drupal_render($maintenance_page);
      $response = new Response('Service unavailable', 503);
      $response->setContent($content);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // In order to change the maintenance status an event subscriber with a
    // priority between 30 and 40 should be added.
    $events[KernelEvents::REQUEST][] = array('onKernelRequestDetermineSiteStatus', 40);
    $events[KernelEvents::REQUEST][] = array('onKernelRequestMaintenance', 30);
    return $events;
  }
}
