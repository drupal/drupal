<?php

/**
 * @file
 * Definition of Drupal\Core\Routing\PathMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Drupal\Core\Database\Connection;

/**
 * Initial matcher to match a route against a built database, by path.
 */
class PathMatcher implements InitialMatcherInterface {

  /**
   * The database connection from which to read route information.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The name of the SQL table from which to read the routes.
   *
   * @var string
   */
  protected $tableName;

  /**
   * Constructs a new PathMatcher.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection object.
   * @param string $table
   *   The table in the database to use for matching.
   */
  public function __construct(Connection $connection, $table = 'router') {
    $this->connection = $connection;
    $this->tableName = $table;
  }

  /**
   * Matches a request against multiple routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Request object against which to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request) {

    $path = rtrim($request->getPathInfo(), '/');

    $parts = array_slice(array_filter(explode('/', $path)), 0, MatcherDumper::MAX_PARTS);

    $ancestors = $this->getCandidateOutlines($parts);

    $routes = $this->connection->query("SELECT name, route FROM {" . $this->connection->escapeTable($this->tableName) . "} WHERE pattern_outline IN (:patterns) ORDER BY fit", array(
      ':patterns' => $ancestors,
    ))
    ->fetchAllKeyed();

    $collection = new RouteCollection();
    foreach ($routes as $name => $route) {
      $route = unserialize($route);
      if (preg_match($route->compile()->getRegex(), $path, $matches)) {
        $collection->add($name, $route);
      }
    }

    if (!count($collection->all())) {
      throw new ResourceNotFoundException();
    }

    return $collection;
  }

  /**
   * Returns an array of path pattern outlines that could match the path parts.
   *
   * @param array $parts
   *   The parts of the path for which we want candidates.
   *
   * @return array
   *   An array of outlines that could match the specified path parts.
   */
  public function getCandidateOutlines(array $parts) {
    $number_parts = count($parts);
    $ancestors = array();
    $length =  $number_parts - 1;
    $end = (1 << $number_parts) - 1;

    // The highest possible mask is a 1 bit for every part of the path. We will
    // check every value down from there to generate a possible outline.
    $masks = range($end, pow($number_parts - 1, 2));

    // Only examine patterns that actually exist as router items (the masks).
    foreach ($masks as $i) {
      if ($i > $end) {
        // Only look at masks that are not longer than the path of interest.
        continue;
      }
      elseif ($i < (1 << $length)) {
        // We have exhausted the masks of a given length, so decrease the length.
        --$length;
      }
      $current = '';
      for ($j = $length; $j >= 0; $j--) {
        // Check the bit on the $j offset.
        if ($i & (1 << $j)) {
          // Bit one means the original value.
          $current .= $parts[$length - $j];
        }
        else {
          // Bit zero means means wildcard.
          $current .= '%';
        }
        // Unless we are at offset 0, add a slash.
        if ($j) {
          $current .= '/';
        }
      }
      $ancestors[] = '/' . $current;
    }
    return $ancestors;
  }
}
