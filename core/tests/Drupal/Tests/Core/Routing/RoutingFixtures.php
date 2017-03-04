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
    $routes = [];
    $routes['route_a'] = [
      'path' => '/path/one',
      'methods' => ['GET'],
    ];
    $routes['route_b'] = [
      'path' => '/path/one',
      'methods' => ['PUT'],
    ];
    $routes['route_c'] = [
      'path' => '/path/two',
      'methods' => ['GET'],
      'requirements' => [
        '_format' => 'json'
      ],
    ];
    $routes['route_d'] = [
      'path' => '/path/three',
    ];
    $routes['route_e'] = [
      'path' => '/path/two',
      'methods' => ['GET', 'HEAD'],
      'requirements' => [
        '_format' => 'html'
      ],
    ];

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
    $route->setMethods(['GET']);
    $collection->add('route_a', $route);

    $route = new Route('path/one');
    $route->setMethods(['PUT']);
    $collection->add('route_b', $route);

    $route = new Route('path/two');
    $route->setMethods(['GET']);
    $route->setRequirement('_format', 'json');
    $collection->add('route_c', $route);

    $route = new Route('path/three');
    $collection->add('route_d', $route);

    $route = new Route('path/two');
    $route->setMethods(['GET', 'HEAD']);
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
    $route->setMethods(['GET']);
    $collection->add('route_a', $route);

    $route = new Route('/path/{thing}/one');
    $route->setMethods(['PUT']);
    $collection->add('route_b', $route);

    $route = new Route('/somewhere/{item}/over/the/rainbow');
    $route->setMethods(['GET']);
    $collection->add('route_c', $route);

    $route = new Route('/another/{thing}/about/{item}');
    $collection->add('route_d', $route);

    $route = new Route('/path/add/one');
    $route->setMethods(['GET', 'HEAD']);
    $collection->add('route_e', $route);

    return $collection;
  }

  /**
   * Returns a complex set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function mixedCaseRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('/path/one');
    $route->setMethods(['GET']);
    $collection->add('route_a', $route);

    $route = new Route('/path/{thing}/one');
    $route->setMethods(['PUT']);
    $collection->add('route_b', $route);

    // Uses Hebrew letter QOF (U+05E7)
    $route = new Route('/somewhere/{item}/over/the/קainbow');
    $route->setMethods(['GET']);
    $collection->add('route_c', $route);

    $route = new Route('/another/{thing}/aboUT/{item}');
    $collection->add('route_d', $route);

    // Greek letters lower case phi (U+03C6) and lower case omega (U+03C9)
    $route = new Route('/place/meφω');
    $route->setMethods(['GET', 'HEAD']);
    $collection->add('route_e', $route);

    return $collection;
  }

  /**
   * Returns a complex set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function duplicatePathsRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('/path/one');
    $route->setMethods(['GET']);
    $collection->add('route_b', $route);

    // Add the routes not in order by route name.
    $route = new Route('/path/one');
    $route->setMethods(['GET']);
    $collection->add('route_a', $route);

    $route = new Route('/path/one');
    $route->setMethods(['GET']);
    $collection->add('route_c', $route);

    $route = new Route('/path/TWO');
    $route->setMethods(['GET']);
    $collection->add('route_d', $route);

    // Greek letters lower case phi (U+03C6) and lower case omega (U+03C9)
    $route = new Route('/place/meφω');
    $route->setMethods(['GET', 'HEAD']);
    $collection->add('route_f', $route);

    $route = new Route('/PLACE/meφω');
    $collection->add('route_e', $route);

    return $collection;
  }

  /**
   * Returns a Content-type restricted set of routes for testing.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function contentRouteCollection() {
    $collection = new RouteCollection();

    $route = new Route('path/three');
    $route->setMethods(['POST']);
    $route->setRequirement('_content_type_format', 'json');
    $collection->add('route_f', $route);

    $route = new Route('path/three');
    $route->setMethods(['PATCH']);
    $route->setRequirement('_content_type_format', 'xml');
    $collection->add('route_g', $route);
    return $collection;
  }

  /**
   * Returns the table definition for the routing fixtures.
   *
   * @return array
   *   Table definitions.
   */
  public function routingTableDefinition() {

    $tables['test_routes'] = [
      'description' => 'Maps paths to various callbacks (access, page and title)',
      'fields' => [
        'name' => [
          'description' => 'Primary Key: Machine name of this route',
          'type' => 'varchar_ascii',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'path' => [
          'description' => 'The path for this URI',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'pattern_outline' => [
          'description' => 'The pattern',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'provider' => [
          'description' => 'The provider grouping to which a route belongs.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'access_callback' => [
          'description' => 'The callback which determines the access to this router path. Defaults to \Drupal\Core\Session\AccountInterface::hasPermission.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ],
        'access_arguments' => [
          'description' => 'A serialized array of arguments for the access callback.',
          'type' => 'blob',
          'not null' => FALSE,
        ],
        'fit' => [
          'description' => 'A numeric representation of how specific the path is.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'number_parts' => [
          'description' => 'Number of parts in this router path.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ],
        'route' => [
          'description' => 'A serialized Route object',
          'type' => 'text',
        ],
      ],
      'indexes' => [
        'fit' => ['fit'],
        'pattern_outline' => ['pattern_outline'],
        'provider' => ['provider'],
      ],
      'primary key' => ['name'],
    ];

    return $tables;
  }

}
