<?php

namespace Drupal\system\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alters routes to add necessary requirements.
 *
 * @see \Drupal\system\Access\SystemAdminMenuBlockAccessCheck
 * @see \Drupal\system\Controller\SystemController::systemAdminMenuBlockPage()
 */
class AccessRouteAlterSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[RoutingEvents::ALTER][] = 'accessAdminMenuBlockPage';
    return $events;
  }

  /**
   * Adds requirements to some System Controller routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The event to process.
   */
  public function accessAdminMenuBlockPage(RouteBuildEvent $event) {
    $routes = $event->getRouteCollection();
    foreach ($routes as $route) {
      // Do not use a leading slash when comparing to the _controller string
      // because the leading slash in a fully-qualified method name is optional.
      if ($route->hasDefault('_controller')) {
        switch (ltrim($route->getDefault('_controller'), '\\')) {
          case 'Drupal\system\Controller\SystemController::systemAdminMenuBlockPage':
            $route->setRequirement('_access_admin_menu_block_page', 'TRUE');
            break;

          case 'Drupal\system\Controller\SystemController::overview':
            $route->setRequirement('_access_admin_overview_page', 'TRUE');
            break;
        }
      }
    }
  }

}
