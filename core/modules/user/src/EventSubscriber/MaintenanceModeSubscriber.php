<?php

/**
 * @file
 * Contains \Drupal\user\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\user\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\EventSubscriber\MaintenanceModeSubscriber as CoreMaintenanceModeSubscriber;

/**
 * Maintenance mode subscriber to logout users.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * Determine whether the page is configured to be offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $user = \Drupal::currentUser();
    $request = $event->getRequest();
    $site_status = $request->attributes->get('_maintenance');
    $path = $request->attributes->get('_system_path');
    if ($site_status == CoreMaintenanceModeSubscriber::SITE_OFFLINE) {
      // If the site is offline, log out unprivileged users.
      if ($user->isAuthenticated() && !$user->hasPermission('access site in maintenance mode')) {
        user_logout();
        // Redirect to homepage.
        $event->setResponse(new RedirectResponse(url('<front>', array('absolute' => TRUE))));
        return;
      }

      if ($user->isAnonymous()) {
        switch ($path) {
          case 'user':
            // Forward anonymous user to login page.
            $event->setResponse(new RedirectResponse(url('user/login', array('absolute' => TRUE))));
            return;
          case 'user/login':
          case 'user/password':
            // Disable offline mode.
            $request->attributes->set('_maintenance', CoreMaintenanceModeSubscriber::SITE_ONLINE);
            break;
          default:
            if (strpos($path, 'user/reset/') === 0) {
              // Disable offline mode.
              $request->attributes->set('_maintenance', CoreMaintenanceModeSubscriber::SITE_ONLINE);
            }
            break;
        }
      }
    }
    if ($user->isAuthenticated()) {
      if ($path == 'user/login') {
        // If user is logged in, redirect to 'user' instead of giving 403.
        $event->setResponse(new RedirectResponse(url('user', array('absolute' => TRUE))));
        return;
      }
      if ($path == 'user/register') {
        // Authenticated user should be redirected to user edit page.
        $event->setResponse(new RedirectResponse(url('user/' . $user->id() . '/edit', array('absolute' => TRUE))));
        return;
      }
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
