<?php

namespace Drupal\user\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\MaintenanceModeEvents;
use Drupal\Core\Site\MaintenanceModeInterface;
use Drupal\user\LogoutFinalizer;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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
   * The logout finalizer closure.
   */
  protected \Closure $logoutFinalizer;

  public function __construct(
    MaintenanceModeInterface $maintenance_mode,
    AccountInterface $account,
    #[AutowireServiceClosure(LogoutFinalizer::class)]
    \Closure $logoutFinalizer,
  ) {
    $this->maintenanceMode = $maintenance_mode;
    $this->account = $account;
    $this->logoutFinalizer = $logoutFinalizer;
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
      ($this->logoutFinalizer)()->finalizeLogout();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[MaintenanceModeEvents::MAINTENANCE_MODE_REQUEST][] = [
      'onMaintenanceModeRequest',
      -900,
    ];
    return $events;
  }

}
