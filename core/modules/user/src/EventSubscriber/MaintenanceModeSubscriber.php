<?php

namespace Drupal\user\EventSubscriber;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\MaintenanceModeEvents;
use Drupal\Core\Site\MaintenanceModeInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Maintenance mode subscriber to log out users.
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
   * Logout users if site is in maintenance mode.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use
   *   \Drupal\user\EventSubscriber::onMaintenanceModeRequest() instead.
   *
   * @see https://www.drupal.org/node/3255799
   */
  public function onKernelRequestMaintenance(RequestEvent $event) {
    @trigger_error('\Drupal\user\EventSubscriber::onKernelRequestMaintenance() is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Drupal\user\EventSubscriber::onMaintenanceModeRequest() instead. See https://www.drupal.org/node/3255799', E_USER_DEPRECATED);
    $request = $event->getRequest();
    $route_match = RouteMatch::createFromRequest($request);
    if ($this->maintenanceMode->applies($route_match)) {
      // If the site is offline, log out unprivileged users.
      if ($this->account->isAuthenticated() && !$this->maintenanceMode->exempt($this->account)) {
        user_logout();
        // Redirect to homepage.
        $event->setResponse(
          new RedirectResponse(Url::fromRoute('<front>')->toString())
        );
      }
    }
  }

  /**
   * Logout users if site is in maintenance mode and user is not exempt.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onMaintenanceModeRequest(RequestEvent $event) {
    // If the site is offline, log out unprivileged users.
    if ($this->account->isAuthenticated()) {
      user_logout();
      // Redirect to homepage.
      $event->setResponse(
        new RedirectResponse(Url::fromRoute('<front>')->toString())
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST][] = [
      'onMaintenanceModeRequest',
      -900,
    ];
    return $events;
  }

}
