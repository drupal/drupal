<?php

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

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

    $parts = array_slide(explode('/', $path), 0, MatcherDumper::MAX_PARTS);

    $number_parts = count($parts);

    $ancestors = $this->getCandidateOutlines($parts);

    // @todo We want to allow matching more than one result because there could
    //   be more than one result with the same path. But how do we do that and
    //   limit by fit?
    $routes = $this->connection
      ->select($this->tableName, 'r')
      ->fields('r', array('name', 'route'))
      ->condition('pattern_outline', $ancestors, 'IN')
      ->condition('number_parts', $number_parts)
      ->execute()
      ->fetchAllKeyed();

    $collection = new RouteCollection();
    foreach ($routes as $name => $route) {
      $collection->add($name, $route);
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
      $candidates[] = $current;
    }

    return $candidates;
  }
}

