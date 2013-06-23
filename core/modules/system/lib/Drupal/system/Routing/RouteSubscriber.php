<?php

/**
 * @file
 * Contains \Drupal\system\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\system\Routing;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Event subscriber for routes.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'createSystemThemeRoutes';
    return $events;
  }

  /**
   * Adds dynamic system theme routes.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function createSystemThemeRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach (list_themes() as $theme) {
      if (!empty($theme->status)) {
        $route = new Route('admin/appearance/settings/' . $theme->name, array(
          '_form' => '\Drupal\system\Form\ThemeSettingsForm', 'theme_name' => $theme->name), array(
          '_permission' => 'administer themes',
        ));
        $collection->add('system_theme_settings_' . $theme->name, $route);
      }
    }
  }

}
