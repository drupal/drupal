<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

use Drupal\Core\Database\Connection;

/**
 * Description of PathMatcher
 *
 * @author crell
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

  public function __construct(Connection $connection, $table = 'router') {
    $this->connection = $connection;
    $this->tableName = $table;
  }

  /**
   * Matches a request against multiple routes.
   *
   * @param Request $request
   *   A Request object against which to match.
   *
   * @return RouteCollection
   *   A RouteCollection of matched routes.
   */
  public function matchRequestPartial(Request $request) {

    $path = $request->getPathInfo();

    $parts = array_slice(array_filter(explode('/', $path)), 0, MatcherDumper::MAX_PARTS);

    $ancestors = $this->getCandidateOutlines($parts);

    $routes = $this->connection->query("SELECT name, route FROM {{$this->tableName}} WHERE pattern_outline IN (:patterns) ORDER BY fit", array(
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
   * @return array
   *   An array of outlines that could match the specified path parts.
   */
  public function getCandidateOutlines(array $parts) {

    $number_parts = count($parts);
    $length =  $number_parts - 1;
    $end = (1 << $number_parts) - 1;
    $candidates = array();

    $start = pow($number_parts-1, 2);

    // The highest possible mask is a 1 bit for every part of the path. We will
    // check every value down from there to generate a possible outline.
    $masks = range($end, $start);

    foreach ($masks as $i) {
      $current = '/';
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
      $candidates[] = $current;
    }

    return $candidates;
  }
}

