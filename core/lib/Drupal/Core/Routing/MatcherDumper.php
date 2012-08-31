<?php

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\Matcher\Dumper\MatcherDumperInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Dumps Route information to a database table.
 */
class MatcherDumper implements MatcherDumperInterface {

  /**
   * The maximum number of path elements for a route pattern;
   */
  const MAX_PARTS = 9;

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

  /**
   * The name of the SQL table to which to dump the routes.
   *
   * @var string
   */
  protected $tableName;

  public function __construct(Connection $connection, $table = 'router') {
    $this->connection = $connection;

    $this->tableName = $table;
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
   * Dumps a set of routes to the router table in the database.
   *
   * Available options:
   *
   *  * route_set:  The route grouping that is being dumped. All existing
   *     routes with this route set will be deleted on dump.
   *  * base_class: The base class name
   *
   * @param $options array
   *   $options An array of options
   */
  public function dump(array $options = array()) {
    $options += array(
      'route_set' => '',
    );

    //$compiled = $this->compileRoutes($this->routes, $route_set);

    // Convert all of the routes into database records.
    $insert = $this->connection->insert($this->tableName)->fields(array(
      'name',
      'route_set',
      'fit',
      'pattern',
      'pattern_outline',
      'number_parts',
      'route',
    ));

    foreach ($this->routes as $name => $route) {
      $route->setOption('compiler_class', '\Drupal\Core\Routing\RouteCompiler');
      $compiled = $route->compile();
      $values = array(
        'name' => $name,
        'route_set' => $options['route_set'],
        'fit' => $compiled->getFit(),
        'pattern' => $compiled->getPattern(),
        'pattern_outline' => $compiled->getPatternOutline(),
        'number_parts' => $compiled->getNumParts(),
        // This is only temporary. We need to strip off the compiled route from
        // route object in order to serialize it. Cloning strips off the
        // compiled route object. Remove this once
        // https://github.com/symfony/symfony/pull/4755 is merged and brought
        // back downstream.
       'route' => serialize(clone($route)),
      );
      $insert->values($values);
    }

    // Delete any old records in this route set first, then insert the new ones.
    // That avoids stale data. The transaction makes it atomic to avoid
    // unstable router states due to random failures.
    $txn = $this->connection->startTransaction();

    $this->connection->delete($this->tableName)
      ->condition('route_set', $options['route_set'])
      ->execute();

    $insert->execute();

    // We want to reuse the dumper for multiple route sets, so on dump, flush
    // the queued routes.
    $this->routes = NULL;

    // Transaction ends here.
  }

  /**
   * Gets the routes to match.
   *
   * @return RouteCollection
   *   A RouteCollection instance representing all routes currently in the
   *   dumper.
   */
  public function getRoutes() {
    return $this->routes;
  }

  protected function compileRoutes(RouteCollection $routes, $route_set) {

    // First pass: separate callbacks from paths, making paths ready for
    // matching. Calculate fitness, and fill some default values.
    $menu = array();
    $masks = array();
    foreach ($routes as $name => $item) {
      $path = $item->getPattern();
      $move = FALSE;

      $parts = explode('/', $path, static::MAX_PARTS);
      $number_parts = count($parts);
      // We store the highest index of parts here to save some work in the fit
      // calculation loop.
      $slashes = $number_parts - 1;

      $num_placeholders = count(array_filter($parts, function($value) {
        return strpos($value, '{') !== FALSE;
      }));

      $fit = $this->getFit($path);

      if ($fit) {
        $move = TRUE;
      }
      else {
        // If there is no placeholder, it fits maximally.
        $fit = (1 << $number_parts) - 1;
      }

      $masks[$fit] = 1;
      $item += array(
        'title' => '',
        'weight' => 0,
        'type' => MENU_NORMAL_ITEM,
        'module' => '',
        '_number_parts' => $number_parts,
        '_parts' => $parts,
        '_fit' => $fit,
      );

      if ($move) {
        $new_path = implode('/', $item['_parts']);
        $menu[$new_path] = $item;
        $sort[$new_path] = $number_parts;
      }
      else {
        $menu[$path] = $item;
        $sort[$path] = $number_parts;
      }
    }

    // Sort the route list.
    array_multisort($sort, SORT_NUMERIC, $menu);
    // Apply inheritance rules.
    foreach ($menu as $path => $v) {
      $item = &$menu[$path];

      for ($i = $item['_number_parts'] - 1; $i; $i--) {
        $parent_path = implode('/', array_slice($item['_parts'], 0, $i));
        if (isset($menu[$parent_path])) {

          $parent = &$menu[$parent_path];

          // If an access callback is not found for a default local task we use
          // the callback from the parent, since we expect them to be identical.
          // In all other cases, the access parameters must be specified.
          if (($item['type'] == MENU_DEFAULT_LOCAL_TASK) && !isset($item['access callback']) && isset($parent['access callback'])) {
            $item['access callback'] = $parent['access callback'];
            if (!isset($item['access arguments']) && isset($parent['access arguments'])) {
              $item['access arguments'] = $parent['access arguments'];
            }
          }

          // Same for theme callbacks.
          if (!isset($item['theme callback']) && isset($parent['theme callback'])) {
            $item['theme callback'] = $parent['theme callback'];
            if (!isset($item['theme arguments']) && isset($parent['theme arguments'])) {
              $item['theme arguments'] = $parent['theme arguments'];
            }
          }
        }
      }
      if (!isset($item['access callback']) && isset($item['access arguments'])) {
        // Default callback.
        $item['access callback'] = 'user_access';
      }
      if (!isset($item['access callback']) || empty($item['page callback'])) {
        $item['access callback'] = 0;
      }
      if (is_bool($item['access callback'])) {
        $item['access callback'] = intval($item['access callback']);
      }

      $item += array(
        'access arguments' => array(),
        'access callback' => '',
        'page arguments' => array(),
        'page callback' => '',
        'delivery callback' => '',
        'title arguments' => array(),
        'title callback' => 't',
        'theme arguments' => array(),
        'theme callback' => '',
        'description' => '',
        'position' => '',
        'context' => 0,
        'tab_parent' => '',
        'tab_root' => $path,
        'path' => $path,
        'file' => '',
        'file path' => '',
        'include file' => '',
      );

      // Calculate out the file to be included for each callback, if any.
      if ($item['file']) {
        $file_path = $item['file path'] ? $item['file path'] : drupal_get_path('module', $item['module']);
        $item['include file'] = $file_path . '/' . $item['file'];
      }
    }

    // Sort the masks so they are in order of descending fit.
    $masks = array_keys($masks);
    rsort($masks);

    return array($menu, $masks);


    // The old menu_router record structure, copied here for easy referencing.
    array(
      'path' => $item['path'],
      'load_functions' => $item['load_functions'],
      'to_arg_functions' => $item['to_arg_functions'],
      'access_callback' => $item['access callback'],
      'access_arguments' => serialize($item['access arguments']),
      'page_callback' => $item['page callback'],
      'page_arguments' => serialize($item['page arguments']),
      'delivery_callback' => $item['delivery callback'],
      'fit' => $item['_fit'],
      'number_parts' => $item['_number_parts'],
      'context' => $item['context'],
      'tab_parent' => $item['tab_parent'],
      'tab_root' => $item['tab_root'],
      'title' => $item['title'],
      'title_callback' => $item['title callback'],
      'title_arguments' => ($item['title arguments'] ? serialize($item['title arguments']) : ''),
      'theme_callback' => $item['theme callback'],
      'theme_arguments' => serialize($item['theme arguments']),
      'type' => $item['type'],
      'description' => $item['description'],
      'position' => $item['position'],
      'weight' => $item['weight'],
      'include_file' => $item['include file'],
    );
  }

  /**
   * Determines the fitness of the provided path.
   *
   * @param string $path
   *   The path whose fitness we want.
   *
   * @return int
   *   The fitness of the path, as an integer.
   */
  public function getFit($path) {
    $fit = 0;

    $parts = explode('/', $path, static::MAX_PARTS);
    foreach ($parts as $k => $part) {
      if (strpos($part, '{') === FALSE) {
        $fit |=  1 << ($slashes - $k);
      }
    }

    return $fit;
  }
}

