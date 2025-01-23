<?php

namespace Drupal\Core\Routing;

use Drupal\Core\Database\DatabaseException;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouteCollection;

use Drupal\Core\Database\Connection;

/**
 * Dumps Route information to a database table.
 *
 * @see \Drupal\Core\Routing\RouteProvider
 */
class MatcherDumper implements MatcherDumperInterface {

  /**
   * The routes to be dumped.
   *
   * @var \Symfony\Component\Routing\RouteCollection
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
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param string $table
   *   (optional) The table to store the route info in. Defaults to 'router'.
   */
  public function __construct(
    protected Connection $connection,
    protected StateInterface $state,
    protected LoggerInterface $logger,
    protected $table = 'router',
  ) {
    if (is_null($this->tableName)) {
      $this->tableName = $table;
    }
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
   *
   * @throws \Exception
   *   Thrown if the table could not be created or the database connection
   *   failed.
   */
  public function dump(array $options = []): string {
    // Convert all of the routes into database records.
    // Accumulate the menu masks on top of any we found before.
    $masks = array_flip($this->state->get('routing.menu_masks.' . $this->tableName, []));
    // Delete any old records first, then insert the new ones. That avoids
    // stale data. The transaction makes it atomic to avoid unstable router
    // states due to random failures.
    try {
      $transaction = $this->connection->startTransaction();
      // We don't use truncate, because it is not guaranteed to be transaction
      // safe.
      try {
        $this->connection->delete($this->tableName)
          ->execute();
      }
      catch (\Exception $e) {
        if (!$this->ensureTableExists()) {
          throw $e;
        }
      }

      // Split the routes into chunks to avoid big INSERT queries.
      $route_chunks = array_chunk($this->routes->all(), 50, TRUE);
      foreach ($route_chunks as $routes) {
        $insert = $this->connection->insert($this->tableName)->fields([
          'name',
          'fit',
          'path',
          'pattern_outline',
          'number_parts',
          'route',
        ]);
        $names = [];
        foreach ($routes as $name => $route) {
          /** @var \Symfony\Component\Routing\Route $route */
          $route->setOption('compiler_class', RouteCompiler::class);
          /** @var \Drupal\Core\Routing\CompiledRoute $compiled */
          $compiled = $route->compile();
          // The fit value is a binary number which has 1 at every fixed path
          // position and 0 where there is a wildcard. We keep track of all such
          // patterns that exist so that we can minimize the number of path
          // patterns we need to check in the RouteProvider.
          $masks[$compiled->getFit()] = 1;
          $names[] = $name;
          $values = [
            'name' => $name,
            'fit' => $compiled->getFit(),
            'path' => $route->getPath(),
            'pattern_outline' => $compiled->getPatternOutline(),
            'number_parts' => $compiled->getNumParts(),
            'route' => serialize($route),
          ];
          $insert->values($values);
        }

        // Insert all new routes.
        $insert->execute();
      }

      // Split the aliases into chunks to avoid big INSERT queries.
      $alias_chunks = array_chunk($this->routes->getAliases(), 50, TRUE);
      foreach ($alias_chunks as $aliases) {
        $insert = $this->connection->insert($this->tableName)->fields([
          'name',
          'route',
          'alias',
        ]);
        foreach ($aliases as $name => $alias) {
          $insert->values([
            'name' => $name,
            'route' => serialize($alias),
            'alias' => $alias->getId(),
          ]);
        }
        $insert->execute();
      }
    }
    catch (\Exception $e) {
      if (isset($transaction)) {
        $transaction->rollBack();
      }
      Error::logException($this->logger, $e);
      throw $e;
    }
    // Sort the masks so they are in order of descending fit.
    $masks = array_keys($masks);
    rsort($masks);
    $this->state->set('routing.menu_masks.' . $this->tableName, $masks);

    $this->routes = NULL;

    return '';
  }

  /**
   * Gets the routes to match.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   A RouteCollection instance representing all routes currently in the
   *   dumper.
   */
  public function getRoutes(): RouteCollection {
    return $this->routes;
  }

  /**
   * Checks if the tree table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   */
  protected function ensureTableExists() {
    try {
      $this->connection->schema()->createTable($this->tableName, $this->schemaDefinition());
    }
    catch (DatabaseException) {
      // If another process has already created the config table, attempting to
      // recreate it will throw an exception. In this case just catch the
      // exception and do nothing.
    }
    catch (\Exception) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Defines the schema for the router table.
   *
   * @return array
   *   The schema API definition for the SQL storage table.
   *
   * @internal
   */
  protected function schemaDefinition() {
    $schema = [
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
        'fit' => [
          'description' => 'A numeric representation of how specific the path is.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ],
        'route' => [
          'description' => 'A serialized Route object',
          'type' => 'blob',
          'size' => 'big',
        ],
        'number_parts' => [
          'description' => 'Number of parts in this router path.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ],
        'alias' => [
          'description' => 'The alias of the route, if applicable.',
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'pattern_outline_parts' => ['pattern_outline', 'number_parts'],
        'alias' => ['alias'],
      ],
      'primary key' => ['name'],
    ];

    return $schema;
  }

}
