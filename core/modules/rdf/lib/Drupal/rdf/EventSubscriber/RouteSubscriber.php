<?php

/**
 * @file
 * Contains RouteSubscriber.
 */

namespace Drupal\rdf\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\rdf\SiteSchema\SiteSchema;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for site-generated schema routes.
 */
class RouteSubscriber implements EventSubscriberInterface {

  /**
   * The site schema manager.
   *
   * @var \Drupal\rdf\SiteSchema\SiteSchemaManager
   */
  protected $siteSchemaManager;

  /**
   * Constructor.
   *
   * @param \Drupal\rdf\SiteSchema\SiteSchemaManager $site_schema_manager
   *   The injected site schema manager.
   */
  public function __construct(SiteSchemaManager $site_schema_manager) {
    $this->siteSchemaManager = $site_schema_manager;
  }

  /**
   * Adds routes for term types in the site-generated schemas.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   */
  public function routes(RouteBuildEvent $event) {

    $collection = $event->getRouteCollection();

    // Add the routes for all of the terms in both schemas.
    foreach ($this->siteSchemaManager->getSchemas() as $schema) {
      $routes = $schema->getRoutes();
      foreach ($routes as $controller => $pattern) {
        $schema_path = $schema->getPath();
        $route = new Route($pattern, array(
          '_controller' => 'Drupal\rdf\SiteSchema\SchemaController::' . $controller,
          'schema_path' => $schema_path,
        ), array(
          '_method' => 'GET',
          '_access' => 'TRUE',
        ));
        // Create the route name to use in the RouteCollection. Remove the
        // trailing slash and replace characters, so that a path such as
        // site-schema/syndication/ becomes rdf.site_schema.syndication.
        $route_name = 'rdf.' . str_replace(array('-','/'), array('_', '.'), substr_replace($schema_path ,"",-1));
        $collection->add($route_name, $route);
      }
    }
  }

  /**
   * Implements EventSubscriberInterface::getSubscribedEvents().
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }
}

