<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\MatcherDumper.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Dumps Route information to a database table.
 */
class MatcherDumper implements MatcherDumperInterface {

  /**
   * The database connection to which to dump route information.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The routes to be dumped.
   *
   * @var Symfony\Component\Routing\RouteCollection
   */
  protected $routes;

  /**
   * The name of the SQL table to which to dump the routes.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Construct the MatcherDumper.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used to store the route
   *   information.
   * @param string $table
   *   (optional) The table to store the route info in. Defaults to 'router'.
   */
  public function __construct(Connection $connection, $table = 'router') {
    $this->connection = $connection;

    $this->tableName = $table;
  }

  /**
   * {@inheritdoc}
   */
  public function addRoutes(RouteCollection $routes) {
    if (empty($this->routes)) {
      $this->routes = $routes;
    }
    else {
      $this->routes->addCollection($routes);
    }
  }

  /**
   * Dumps a set of routes to the router table in the database.
   *
   * Available options:
   * - provider: The route grouping that is being dumped. All existing
   *   routes with this provider will be deleted on dump.
   * - base_class: The base class name.
   *
   * @param array $options
   *   An array of options.
   */
  public function dump(array $options = array()) {
    $options += array(
      'provider' => '',
    );

    // Convert all of the routes into database records.
    $insert = $this->connection->insert($this->tableName)->fields(array(
      'name',
      'provider',
      'fit',
      'path',
      'pattern_outline',
      'number_parts',
      'route',
    ));

    foreach ($this->routes as $name => $route) {
      $route->setOption('compiler_class', '\Drupal\Core\Routing\RouteCompiler');
      $compiled = $route->compile();
      $values = array(
        'name' => $name,
        'provider' => $options['provider'],
        'fit' => $compiled->getFit(),
        'path' => $compiled->getPath(),
        'pattern_outline' => $compiled->getPatternOutline(),
        'number_parts' => $compiled->getNumParts(),
        'route' => serialize($route),
      );
      $insert->values($values);
    }

    // Delete any old records in this provider first, then insert the new ones.
    // That avoids stale data. The transaction makes it atomic to avoid
    // unstable router states due to random failures.
    $txn = $this->connection->startTransaction();

    $this->connection->delete($this->tableName)
      ->condition('provider', $options['provider'])
      ->execute();

    $insert->execute();

    // We want to reuse the dumper for multiple providers, so on dump, flush
    // the queued routes.
    $this->routes = NULL;

    // Transaction ends here.
  }

  /**
   * Gets the routes to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection instance representing all routes currently in the
   *   dumper.
   */
  public function getRoutes() {
    return $this->routes;
  }

}
