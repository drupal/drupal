<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\MatcherDumper.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\State\StateInterface;
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
   * @var \Symfony\Component\Routing\RouteCollection
   */
  protected $routes;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param string $table
   *   (optional) The table to store the route info in. Defaults to 'router'.
   */
  public function __construct(Connection $connection, StateInterface $state, $table = 'router') {
    $this->connection = $connection;
    $this->state = $state;

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
    // Convert all of the routes into database records.
    // Accumulate the menu masks on top of any we found before.
    $masks = array_flip($this->state->get('routing.menu_masks.' . $this->tableName, array()));
    // Delete any old records first, then insert the new ones. That avoids
    // stale data. The transaction makes it atomic to avoid unstable router
    // states due to random failures.
    $transaction = $this->connection->startTransaction();
    try {
      // We don't use truncate, because it is not guaranteed to be transaction
      // safe.
      $this->connection->delete($this->tableName)->execute();

      // Split the routes into chunks to avoid big INSERT queries.
      $route_chunks = array_chunk($this->routes->all(), 50, TRUE);
      foreach ($route_chunks as $routes) {
        $insert = $this->connection->insert($this->tableName)->fields(array(
          'name',
          'fit',
          'path',
          'pattern_outline',
          'number_parts',
          'route',
        ));
        $names = array();
        foreach ($routes as $name => $route) {
          /** @var \Symfony\Component\Routing\Route $route */
          $route->setOption('compiler_class', '\Drupal\Core\Routing\RouteCompiler');
          $compiled = $route->compile();
          // The fit value is a binary number which has 1 at every fixed path
          // position and 0 where there is a wildcard. We keep track of all such
          // patterns that exist so that we can minimize the number of path
          // patterns we need to check in the RouteProvider.
          $masks[$compiled->getFit()] = 1;
          $names[] = $name;
          $values = array(
            'name' => $name,
            'fit' => $compiled->getFit(),
            'path' => $compiled->getPath(),
            'pattern_outline' => $compiled->getPatternOutline(),
            'number_parts' => $compiled->getNumParts(),
            'route' => serialize($route),
          );
          $insert->values($values);
        }

        // Insert all new routes.
        $insert->execute();
      }


    } catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception('Routing', $e);
      throw $e;
    }
    // Sort the masks so they are in order of descending fit.
    $masks = array_keys($masks);
    rsort($masks);
    $this->state->set('routing.menu_masks.' . $this->tableName, $masks);

    $this->routes = NULL;
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
