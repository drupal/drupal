<?php

/**
 * @file
 * Contains \Drupal\user\EventSubscriber\MaintenanceModeSubscriber.
 */

namespace Drupal\user\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\MaintenanceModeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Maintenance mode subscriber to logout users.
 */
class MaintenanceModeSubscriber implements EventSubscriberInterface {

  /**
   * The maintenance mode.
   *
   * @var \Drupal\Core\Site\MaintenanceMode
   */
  protected $maintenanceMode;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a new MaintenanceModeSubscriber.
   *
   * @param \Drupal\Core\Site\MaintenanceModeInterface $maintenance_mode
   *   The maintenance mode.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(MaintenanceModeInterface $maintenance_mode, AccountInterface $account) {
    $this->maintenanceMode = $maintenance_mode;
    $this->account = $account;
  }

  /**
   * Determine whether the page is configured to be offline.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestMaintenance(GetResponseEvent $event) {
    $request = $event->getRequest();
    $route_match = RouteMatch::createFromRequest($request);
    $path = $request->attributes->get('_system_path');
    if ($this->maintenanceMode->applies($route_match)) {
      // If the site is offline, log out unprivileged users.
      if ($this->account->isAuthenticated() && !$this->maintenanceMode->exempt($this->account)) {
        user_logout();
        // Redirect to homepage.
        $event->setResponse(new RedirectResponse(url('<front>', array('absolute' => TRUE))));
        return;
      }

      if ($this->account->isAnonymous() && $path == 'user') {
        // Forward anonymous user to login page.
        $event->setResponse(new RedirectResponse(url('user/login', array('absolute' => TRUE))));
        return;
      }
    }
    if ($this->account->isAuthenticated()) {
      if ($path == 'user/login') {
        // If user is logged in, redirect to 'user' instead of giving 403.
        $event->setResponse(new RedirectResponse(url('user', array('absolute' => TRUE))));
        return;
      }
      if ($path == 'user/register') {
        // Authenticated user should be redirected to user edit page.
        $event->setResponse(new RedirectResponse(url('user/' . $this->account->id() . '/edit', array('absolute' => TRUE))));
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
