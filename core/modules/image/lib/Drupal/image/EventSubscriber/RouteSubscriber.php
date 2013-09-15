<?php

/**
 * @file
 * Contains \Drupal\image\EventSubscriber\RouteSubscriber.
 */

namespace Drupal\image\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'dynamicRoutes';
    return $events;
  }

  /**
   * Registers dynamic routes for image styles.
   *
   * Generate image derivatives of publicly available files. If clean URLs are
   * disabled, image derivatives will always be served through the menu system.
   * If clean URLs are enabled and the image derivative already exists, PHP will
   * be bypassed.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function dynamicRoutes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();

    $directory_path = file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath();

    $route = new Route('/' . $directory_path . '/styles/{image_style}/{scheme}',
      array(
        '_controller' => 'Drupal\image\Controller\ImageStyleDownloadController::deliver',
      ),
      array(
        '_access' => 'TRUE',
      )
    );
    $collection->add('image.style_public', $route);
  }

}
