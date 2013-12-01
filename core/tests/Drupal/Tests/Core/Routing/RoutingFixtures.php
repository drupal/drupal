<?php

namespace Drupal\Tests\Core\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Utility methods to generate sample data, database configuration, etc.
 */
class RoutingFixtures {

  /**
   * Create the tables required for the sample data.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to use to create the tables.
   */
  public function createTables(Connection $connection) {
    $tables = $this->routingTableDefinition();
    $schema = $connection->schema();

    foreach ($tables as $name => $table) {
      $schema->dropTable($name);
      $schema->createTable($name, $table);
    }
  }

  /**
   * Drop the tables used for the sample data.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The connection to use to drop the tables.
   */
  public function dropTables(Connection $connection) {
    $tables = $this->routingTableDefinition();
    $schema = $connection->schema();

    foreach ($tables as $name => $table) {
      $schema->dropTable($name);
    }
  }

  /**
   * Returns a static version of the routes.
   */
  public function staticSampleRouteCollection() {
    $routes = array();
    $routes['route_a'] = array(
      'path' => '/path/one',
      'requirements' => array(
        '_method' => 'GET',
      ),
    );
    $routes['route_b'] = array(
      'path' => '/path/one',
      'requirements' => array(
        '_method' => 'PUT',
      ),
    );
    $routes['route_c'] = array(
      'path' => '/path/two',
      'requirements' => array(
        '_method' => 'GET',
        '_format' => 'json'
      ),
    );
    $routes['route_d'] = array(
      'path' => '/path/three',
    );
    $routes['route_e'] = array(
      'path' => '/path/two',
      'requirements' => array(
        '_method' => 'GET|HEAD',
        '_format' => 'html'
      ),
    );

    return $routes;
  }

  /**
   * Returns a standard set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function sampleRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('path/one');
    $route->setRequirement('_method', 'GET');
    $collection->add('route_a', $route);

    $route = new Route('path/one');
    $route->setRequirement('_method', 'PUT');
    $collection->add('route_b', $route);

    $route = new Route('path/two');
    $route->setRequirement('_method', 'GET');
    $route->setRequirement('_format', 'json');
    $collection->add('route_c', $route);

    $route = new Route('path/three');
    $collection->add('route_d', $route);

    $route = new Route('path/two');
    $route->setRequirement('_method', 'GET|HEAD');
    $route->setRequirement('_format', 'html');
    $collection->add('route_e', $route);

    return $collection;
  }

  /**
   * Returns a complex set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function complexRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('/path/{thing}/one');
    $route->setRequirement('_method', 'GET');
    $collection->add('route_a', $route);

    $route = new Route('/path/{thing}/one');
    $route->setRequirement('_method', 'PUT');
    $collection->add('route_b', $route);

    $route = new Route('/somewhere/{item}/over/the/rainbow');
    $route->setRequirement('_method', 'GET');
    $collection->add('route_c', $route);

    $route = new Route('/another/{thing}/about/{item}');
    $collection->add('route_d', $route);

    $route = new Route('/path/add/one');
    $route->setRequirement('_method', 'GET|HEAD');
    $collection->add('route_e', $route);

    return $collection;
  }

  /**
   * Returns the table definition for the routing fixtures.
   *
   * @return array
   *   Table definitions.
   */
  public function routingTableDefinition() {

    $tables['test_routes'] = array(
      'description' => 'Maps paths to various callbacks (access, page and title)',
      'fields' => array(
        'name' => array(
          'description' => 'Primary Key: Machine name of this route',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'path' => array(
          'description' => 'The path for this URI',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'pattern_outline' => array(
          'description' => 'The pattern',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'provider' => array(
          'description' => 'The provider grouping to which a route belongs.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'access_callback' => array(
          'description' => 'The callback which determines the access to this router path. Defaults to user_access.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'access_arguments' => array(
          'description' => 'A serialized array of arguments for the access callback.',
          'type' => 'blob',
          'not null' => FALSE,
        ),
        'fit' => array(
          'description' => 'A numeric representation of how specific the path is.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'number_parts' => array(
          'description' => 'Number of parts in this router path.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'route' => array(
          'description' => 'A serialized Route object',
          'type' => 'text',
        ),
      ),
      'indexes' => array(
        'fit' => array('fit'),
        'pattern_outline' => array('pattern_outline'),
        'provider' => array('provider'),
      ),
      'primary key' => array('name'),
    );

    return $tables;
  }
}
