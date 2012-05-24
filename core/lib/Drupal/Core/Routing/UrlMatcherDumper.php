<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Description of UrlMatcherDumper
 *
 * @author crell
 */
class UrlMatcherDumper implements MatcherDumperInterface {

  /**
   * The database connection to which to dump route information.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The routes to be dumped.
   *
   * @var Symfony\Component\Routing\RouteCollection
   */
  protected $routes;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Adds additional routes to be dumped.
   *
   * @param RouteCollection $routes
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
   * Dumps a set of routes to a PHP class.
   *
   * Available options:
   *
   *  * class:      The class name
   *  * base_class: The base class name
   *
   * @param  array  $options An array of options
   *
   * @return string A PHP class representing the matcher class
   */
  function dump(array $options = array()) {

  }

  /**
   * Gets the routes to match.
   *
   * @return RouteCollection A RouteCollection instance
   */
  function getRoutes() {
    return $this->routes;
  }
}

