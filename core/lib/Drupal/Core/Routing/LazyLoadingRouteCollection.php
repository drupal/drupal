<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\LazyLoadingRouteCollection.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\Database\Connection;
use Iterator;

/**
 * Provides a route collection that lists all routes of drupal.
 *
 * Internally this does load multiple routes over time, so it never have all the
 * routes stored in memory.
 */
class LazyLoadingRouteCollection implements Iterator {

  /**
   * Stores the current loaded routes.
   *
   * @var \Symfony\Component\Routing\Route[]
   */
  protected $elements;

  /**
   * Contains the amount of route which are loaded on each sql query.
   */
  const ROUTE_LOADED_PER_TIME = 50;

  /**
   * Contains the current item the iterator points to.
   *
   * @var int
   */
  protected $currentRoute = 0;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The name of the SQL table from which to read the routes.
   *
   * @var string
   */
  protected $tableName;

  /**
   * The number of routes in the router table.
   *
   * @var int
   */
  protected $count;

  /**
   * Creates a LazyLoadingRouteCollection instance.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param string $table
   *   (optional) The table to retrieve the route information.
   */
  public function __construct(Connection $database, $table = 'router') {
    $this->database = $database;
    $this->tableName = $table;
  }

  /**
   * Loads the next routes into the elements array.
   *
   * @param int $offset
   *   The offset used in the db query.
   */
  protected function loadNextElements($offset) {
    $this->elements = array();

    $query = $this->database->select($this->tableName);
    $query->addField($this->tableName, 'name');
    $query->addField($this->tableName, 'route');
    $query->orderBy('name', 'ASC');
    $query->range($offset, static::ROUTE_LOADED_PER_TIME);
    $result = $query->execute()->fetchAllKeyed();

    $routes = array();
    foreach ($result as $name => $route) {
      $routes[$name] = unserialize($route);
    }
    $this->elements = $routes;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    if (!isset($this->count)) {
      $this->count = (int) $this->database->select($this->tableName)->countQuery()->execute();
    }
    return $this->count;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return current($this->elements);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $result = next($this->elements);
    if ($result === FALSE) {
      $this->loadNextElements($this->currentRoute + 1);
    }
    $this->currentRoute++;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->elements);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return key($this->elements);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->currentRoute = 0;
    $this->loadNextElements($this->currentRoute);
  }

}
