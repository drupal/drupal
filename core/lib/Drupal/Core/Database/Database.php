<?php

namespace Drupal\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Primary front-controller for the database system.
 *
 * This class is uninstantiatable and un-extendable. It acts to encapsulate
 * all control and shepherding of database connections into a single location
 * without the use of globals.
 */
abstract class Database {

  /**
   * Flag to indicate a query call should simply return NULL.
   *
   * This is used for queries that have no reasonable return value anyway, such
   * as INSERT statements to a table without a serial primary key.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3185520
   */
  const RETURN_NULL = 0;

  /**
   * Flag to indicate a query call should return the prepared statement.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3185520
   */
  const RETURN_STATEMENT = 1;

  /**
   * Flag to indicate a query call should return the number of matched rows.
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3185520
   */
  const RETURN_AFFECTED = 2;

  /**
   * Flag to indicate a query call should return the "last insert id".
   *
   * @deprecated in drupal:9.4.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3185520
   */
  const RETURN_INSERT_ID = 3;

  /**
   * A nested array of active connections, keyed by database name and target.
   *
   * @var array
   */
  protected static $connections = [];

  /**
   * A processed copy of the database connection information from settings.php.
   *
   * @var array
   */
  protected static $databaseInfo = [];

  /**
   * A list of key/target credentials to simply ignore.
   *
   * @var array
   */
  protected static $ignoreTargets = [];

  /**
   * The key of the currently active database connection.
   *
   * @var string
   */
  protected static $activeKey = 'default';

  /**
   * An array of active query log objects.
   *
   * Every connection has one and only one logger object for all targets and
   * logging keys.
   *
   * array(
   *   '$db_key' => DatabaseLog object.
   * );
   *
   * @var array
   */
  protected static $logs = [];

  /**
   * Starts logging a given logging key on the specified connection.
   *
   * @param string $logging_key
   *   The logging key to log.
   * @param string $key
   *   The database connection key for which we want to log.
   *
   * @return \Drupal\Core\Database\Log
   *   The query log object. Note that the log object does support richer
   *   methods than the few exposed through the Database class, so in some
   *   cases it may be desirable to access it directly.
   *
   * @see \Drupal\Core\Database\Log
   */
  final public static function startLog($logging_key, $key = 'default') {
    if (empty(self::$logs[$key])) {
      self::$logs[$key] = new Log($key);

      // Every target already active for this connection key needs to have the
      // logging object associated with it.
      if (!empty(self::$connections[$key])) {
        foreach (self::$connections[$key] as $connection) {
          $connection->setLogger(self::$logs[$key]);
        }
      }
    }

    self::$logs[$key]->start($logging_key);
    return self::$logs[$key];
  }

  /**
   * Retrieves the queries logged on for given logging key.
   *
   * This method also ends logging for the specified key. To get the query log
   * to date without ending the logger request the logging object by starting
   * it again (which does nothing to an open log key) and call methods on it as
   * desired.
   *
   * @param string $logging_key
   *   The logging key to log.
   * @param string $key
   *   The database connection key for which we want to log.
   *
   * @return array
   *   The query log for the specified logging key and connection.
   *
   * @see \Drupal\Core\Database\Log
   */
  final public static function getLog($logging_key, $key = 'default') {
    if (empty(self::$logs[$key])) {
      return [];
    }
    $queries = self::$logs[$key]->get($logging_key);
    self::$logs[$key]->end($logging_key);
    return $queries;
  }

  /**
   * Gets the connection object for the specified database key and target.
   *
   * @param string $target
   *   The database target name.
   * @param string $key
   *   The database connection key. Defaults to NULL which means the active key.
   *
   * @return \Drupal\Core\Database\Connection
   *   The corresponding connection object.
   */
  final public static function getConnection($target = 'default', $key = NULL) {
    if (!isset($key)) {
      // By default, we want the active connection, set in setActiveConnection.
      $key = self::$activeKey;
    }
    // If the requested target does not exist, or if it is ignored, we fall back
    // to the default target. The target is typically either "default" or
    // "replica", indicating to use a replica SQL server if one is available. If
    // it's not available, then the default/primary server is the correct server
    // to use.
    if (!empty(self::$ignoreTargets[$key][$target]) || !isset(self::$databaseInfo[$key][$target])) {
      $target = 'default';
    }

    if (!isset(self::$connections[$key][$target])) {
      // If necessary, a new connection is opened.
      self::$connections[$key][$target] = self::openConnection($key, $target);
    }
    return self::$connections[$key][$target];
  }

  /**
   * Determines if there is an active connection.
   *
   * Note that this method will return FALSE if no connection has been
   * established yet, even if one could be.
   *
   * @return bool
   *   TRUE if there is at least one database connection established, FALSE
   *   otherwise.
   */
  final public static function isActiveConnection() {
    return !empty(self::$activeKey) && !empty(self::$connections) && !empty(self::$connections[self::$activeKey]);
  }

  /**
   * Sets the active connection to the specified key.
   *
   * @return string|null
   *   The previous database connection key.
   */
  final public static function setActiveConnection($key = 'default') {
    if (!empty(self::$databaseInfo[$key])) {
      $old_key = self::$activeKey;
      self::$activeKey = $key;
      return $old_key;
    }
  }

  /**
   * Process the configuration file for database information.
   *
   * @param array $info
   *   The database connection information, as defined in settings.php. The
   *   structure of this array depends on the database driver it is connecting
   *   to.
   */
  final public static function parseConnectionInfo(array $info) {
    // If there is no "driver" property, then we assume it's an array of
    // possible connections for this target. Pick one at random. That allows
    // us to have, for example, multiple replica servers.
    if (empty($info['driver'])) {
      $info = $info[mt_rand(0, count($info) - 1)];
    }

    // Prefix information, default to an empty prefix.
    $info['prefix'] = $info['prefix'] ?? '';

    // Backwards compatibility layer for Drupal 8 style database connection
    // arrays. Those have the wrong 'namespace' key set, or not set at all
    // for core supported database drivers.
    if (empty($info['namespace']) || (strpos($info['namespace'], 'Drupal\\Core\\Database\\Driver\\') === 0)) {
      switch (strtolower($info['driver'])) {
        case 'mysql':
          $info['namespace'] = 'Drupal\\mysql\\Driver\\Database\\mysql';
          break;

        case 'pgsql':
          $info['namespace'] = 'Drupal\\pgsql\\Driver\\Database\\pgsql';
          break;

        case 'sqlite':
          $info['namespace'] = 'Drupal\\sqlite\\Driver\\Database\\sqlite';
          break;
      }
    }
    // Backwards compatibility layer for Drupal 8 style database connection
    // arrays. Those do not have the 'autoload' key set for core database
    // drivers.
    if (empty($info['autoload'])) {
      switch (trim($info['namespace'], '\\')) {
        case "Drupal\\mysql\\Driver\\Database\\mysql":
          $info['autoload'] = "core/modules/mysql/src/Driver/Database/mysql/";
          break;

        case "Drupal\\pgsql\\Driver\\Database\\pgsql":
          $info['autoload'] = "core/modules/pgsql/src/Driver/Database/pgsql/";
          break;

        case "Drupal\\sqlite\\Driver\\Database\\sqlite":
          $info['autoload'] = "core/modules/sqlite/src/Driver/Database/sqlite/";
          break;
      }
    }

    return $info;
  }

  /**
   * Adds database connection information for a given key/target.
   *
   * This method allows to add new connections at runtime.
   *
   * Under normal circumstances the preferred way to specify database
   * credentials is via settings.php. However, this method allows them to be
   * added at arbitrary times, such as during unit tests, when connecting to
   * admin-defined third party databases, etc. Use
   * \Drupal\Core\Database\Database::setActiveConnection to select the
   * connection to use.
   *
   * If the given key/target pair already exists, this method will be ignored.
   *
   * @param string $key
   *   The database key.
   * @param string $target
   *   The database target name.
   * @param array $info
   *   The database connection information, as defined in settings.php. The
   *   structure of this array depends on the database driver it is connecting
   *   to.
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The class loader. Used for adding the database driver to the autoloader
   *   if $info['autoload'] is set.
   * @param string $app_root
   *   The app root.
   *
   * @see \Drupal\Core\Database\Database::setActiveConnection
   */
  final public static function addConnectionInfo($key, $target, array $info, $class_loader = NULL, $app_root = NULL) {
    if (empty(self::$databaseInfo[$key][$target])) {
      $info = self::parseConnectionInfo($info);
      self::$databaseInfo[$key][$target] = $info;

      // If the database driver is provided by a module, then its code may need
      // to be instantiated prior to when the module's root namespace is added
      // to the autoloader, because that happens during service container
      // initialization but the container definition is likely in the database.
      // Therefore, allow the connection info to specify an autoload directory
      // for the driver.
      if (isset($info['autoload']) && $class_loader && $app_root) {
        $class_loader->addPsr4($info['namespace'] . '\\', $app_root . '/' . $info['autoload']);
      }
    }
  }

  /**
   * Gets information on the specified database connection.
   *
   * @param string $key
   *   (optional) The connection key for which to return information.
   *
   * @return array|null
   */
  final public static function getConnectionInfo($key = 'default') {
    if (!empty(self::$databaseInfo[$key])) {
      return self::$databaseInfo[$key];
    }
  }

  /**
   * Gets connection information for all available databases.
   *
   * @return array
   */
  final public static function getAllConnectionInfo() {
    return self::$databaseInfo;
  }

  /**
   * Sets connection information for multiple databases.
   *
   * @param array $databases
   *   A multi-dimensional array specifying database connection parameters, as
   *   defined in settings.php.
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The class loader. Used for adding the database driver(s) to the
   *   autoloader if $databases[$key][$target]['autoload'] is set.
   * @param string $app_root
   *   The app root.
   */
  final public static function setMultipleConnectionInfo(array $databases, $class_loader = NULL, $app_root = NULL) {
    foreach ($databases as $key => $targets) {
      foreach ($targets as $target => $info) {
        self::addConnectionInfo($key, $target, $info, $class_loader, $app_root);
      }
    }
  }

  /**
   * Rename a connection and its corresponding connection information.
   *
   * @param string $old_key
   *   The old connection key.
   * @param string $new_key
   *   The new connection key.
   *
   * @return bool
   *   TRUE in case of success, FALSE otherwise.
   */
  final public static function renameConnection($old_key, $new_key) {
    if (!empty(self::$databaseInfo[$old_key]) && empty(self::$databaseInfo[$new_key])) {
      // Migrate the database connection information.
      self::$databaseInfo[$new_key] = self::$databaseInfo[$old_key];
      unset(self::$databaseInfo[$old_key]);

      // Migrate over the DatabaseConnection object if it exists.
      if (isset(self::$connections[$old_key])) {
        self::$connections[$new_key] = self::$connections[$old_key];
        unset(self::$connections[$old_key]);
      }

      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Remove a connection and its corresponding connection information.
   *
   * @param string $key
   *   The connection key.
   *
   * @return bool
   *   TRUE in case of success, FALSE otherwise.
   */
  final public static function removeConnection($key) {
    if (isset(self::$databaseInfo[$key])) {
      self::closeConnection(NULL, $key);
      unset(self::$databaseInfo[$key]);
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Opens a connection to the server specified by the given key and target.
   *
   * @param string $key
   *   The database connection key, as specified in settings.php. The default is
   *   "default".
   * @param string $target
   *   The database target to open.
   *
   * @throws \Drupal\Core\Database\ConnectionNotDefinedException
   * @throws \Drupal\Core\Database\DriverNotSpecifiedException
   */
  final protected static function openConnection($key, $target) {
    // If the requested database does not exist then it is an unrecoverable
    // error.
    if (!isset(self::$databaseInfo[$key])) {
      throw new ConnectionNotDefinedException('The specified database connection is not defined: ' . $key);
    }

    if (!self::$databaseInfo[$key][$target]['driver']) {
      throw new DriverNotSpecifiedException('Driver not specified for this database connection: ' . $key);
    }

    $driver_class = self::$databaseInfo[$key][$target]['namespace'] . '\\Connection';

    $client_connection = $driver_class::open(self::$databaseInfo[$key][$target]);
    $new_connection = new $driver_class($client_connection, self::$databaseInfo[$key][$target]);
    $new_connection->setTarget($target);
    $new_connection->setKey($key);

    // If we have any active logging objects for this connection key, we need
    // to associate them with the connection we just opened.
    if (!empty(self::$logs[$key])) {
      $new_connection->setLogger(self::$logs[$key]);
    }

    return $new_connection;
  }

  /**
   * Closes a connection to the server specified by the given key and target.
   *
   * @param string $target
   *   The database target name.  Defaults to NULL meaning that all target
   *   connections will be closed.
   * @param string $key
   *   The database connection key. Defaults to NULL which means the active key.
   */
  public static function closeConnection($target = NULL, $key = NULL) {
    // Gets the active connection by default.
    if (!isset($key)) {
      $key = self::$activeKey;
    }
    if (isset($target)) {
      unset(self::$connections[$key][$target]);
    }
    else {
      unset(self::$connections[$key]);
    }
    // Force garbage collection to run. This ensures that client connection
    // objects and results in the connection being closed are destroyed.
    gc_collect_cycles();
  }

  /**
   * Instructs the system to temporarily ignore a given key/target.
   *
   * At times we need to temporarily disable replica queries. To do so, call this
   * method with the database key and the target to disable. That database key
   * will then always fall back to 'default' for that key, even if it's defined.
   *
   * @param string $key
   *   The database connection key.
   * @param string $target
   *   The target of the specified key to ignore.
   */
  public static function ignoreTarget($key, $target) {
    self::$ignoreTargets[$key][$target] = TRUE;
  }

  /**
   * Converts a URL to a database connection info array.
   *
   * @param string $url
   *   The URL.
   * @param string $root
   *   The root directory of the Drupal installation.
   * @param bool|null $include_test_drivers
   *   (optional) Whether to include test extensions. If FALSE, all 'tests'
   *   directories are excluded in the search. When NULL will be determined by
   *   the extension_discovery_scan_tests setting.
   *
   * @return array
   *   The database connection info.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided URL does not meet the minimum
   *   requirements.
   * @throws \RuntimeException
   *   Exception thrown when a module provided database driver does not exist.
   */
  public static function convertDbUrlToConnectionInfo($url, $root, ?bool $include_test_drivers = NULL) {
    // Check that the URL is well formed, starting with 'scheme://', where
    // 'scheme' is a database driver name.
    if (preg_match('/^(.*):\/\//', $url, $matches) !== 1) {
      throw new \InvalidArgumentException("Missing scheme in URL '$url'");
    }
    $driver = $matches[1];

    // Determine if the database driver is provided by a module.
    // @todo https://www.drupal.org/project/drupal/issues/3250999. Refactor when
    // all database drivers are provided by modules.
    $module = NULL;
    $connection_class = NULL;
    $url_components = parse_url($url);
    $url_component_query = $url_components['query'] ?? '';
    parse_str($url_component_query, $query);

    // Add the module key for core database drivers when the module key is not
    // set.
    if (!isset($query['module']) && in_array($driver, ['mysql', 'pgsql', 'sqlite'], TRUE)) {
      $query['module'] = $driver;
    }

    if (isset($query['module']) && $query['module']) {
      $module = $query['module'];
      // Set up an additional autoloader. We don't use the main autoloader as
      // this method can be called before Drupal is installed and is never
      // called during regular runtime.
      $namespace = "Drupal\\$module\\Driver\\Database\\$driver";
      $psr4_base_directory = Database::findDriverAutoloadDirectory($namespace, $root, $include_test_drivers);
      $additional_class_loader = new ClassLoader();
      $additional_class_loader->addPsr4($namespace . '\\', $psr4_base_directory);
      $additional_class_loader->register(TRUE);
      $connection_class = $namespace . '\\Connection';
    }

    if (!$module) {
      // Determine the connection class to use. Discover if the URL has a valid
      // driver scheme for a Drupal 8 style custom driver.
      // @todo Remove this in Drupal 10.
      $connection_class = "Drupal\\Driver\\Database\\{$driver}\\Connection";
    }

    if (!class_exists($connection_class)) {
      throw new \InvalidArgumentException("Can not convert '$url' to a database connection, class '$connection_class' does not exist");
    }

    $options = $connection_class::createConnectionOptionsFromUrl($url, $root);

    // If the driver is provided by a module add the necessary information to
    // autoload the code.
    // @see \Drupal\Core\Site\Settings::initialize()
    if (isset($psr4_base_directory)) {
      $options['autoload'] = $psr4_base_directory;
    }

    return $options;
  }

  /**
   * Finds the directory to add to the autoloader for the driver's namespace.
   *
   * For Drupal sites that manage their codebase with Composer, the package
   * that provides the database driver should add the driver's namespace to
   * Composer's autoloader. However, to support sites that add Drupal modules
   * without Composer, and because the database connection must be established
   * before Drupal adds the module's entire namespace to the autoloader, the
   * database connection info array can include an "autoload" key containing
   * the autoload directory for the driver's namespace. For requests that
   * connect to the database via a connection info array, the value of the
   * "autoload" key is automatically added to the autoloader.
   *
   * This method can be called to find the default value of that key when the
   * database connection info array isn't available. This includes:
   * - Console commands and test runners that connect to a database specified
   *   by a database URL rather than a connection info array.
   * - During installation, prior to the connection info array being written to
   *   settings.php.
   *
   * This method returns the directory that must be added to the autoloader for
   * the given namespace.
   * - If the namespace is a sub-namespace of a Drupal module, then this method
   *   returns the autoload directory for that namespace, allowing Drupal
   *   modules containing database drivers to be added to a Drupal website
   *   without Composer.
   * - If the namespace is a sub-namespace of Drupal\Core or Drupal\Driver,
   *   then this method returns FALSE, because Drupal core's autoloader already
   *   includes these namespaces, so no additional autoload directory is
   *   required for any code within them.
   * - If the namespace is anything else, then this method returns FALSE,
   *   because neither drupal_get_database_types() nor
   *   static::convertDbUrlToConnectionInfo() support that anyway. One can
   *   manually edit the connection info array in settings.php to reference
   *   any arbitrary namespace, but requests using that would use the
   *   corresponding 'autoload' key in that connection info rather than calling
   *   this method.
   *
   * @param string $namespace
   *   The database driver's namespace.
   * @param string $root
   *   The root directory of the Drupal installation.
   * @param bool|null $include_test_drivers
   *   (optional) Whether to include test extensions. If FALSE, all 'tests'
   *   directories are excluded in the search. When NULL will be determined by
   *   the extension_discovery_scan_tests setting.
   *
   * @return string|false
   *   The PSR-4 directory to add to the autoloader for the namespace if the
   *   namespace is a sub-namespace of a Drupal module. FALSE otherwise, as
   *   explained above.
   *
   * @throws \RuntimeException
   *   Exception thrown when a module provided database driver does not exist.
   */
  public static function findDriverAutoloadDirectory($namespace, $root, ?bool $include_test_drivers = NULL) {
    // As explained by this method's documentation, return FALSE if the
    // namespace is not a sub-namespace of a Drupal module.
    if (!static::isWithinModuleNamespace($namespace)) {
      return FALSE;
    }

    // Extract the module information from the namespace.
    [, $module, $module_relative_namespace] = explode('\\', $namespace, 3);

    // The namespace is within a Drupal module. Find the directory where the
    // module is located.
    $extension_discovery = new ExtensionDiscovery($root, FALSE, []);
    $modules = $extension_discovery->scan('module', $include_test_drivers);
    if (!isset($modules[$module])) {
      throw new \RuntimeException(sprintf("Cannot find the module '%s' for the database driver namespace '%s'", $module, $namespace));
    }
    $module_directory = $modules[$module]->getPath();

    // All code within the Drupal\MODULE namespace is expected to follow a
    // PSR-4 layout within the module's "src" directory.
    $driver_directory = $module_directory . '/src/' . str_replace('\\', '/', $module_relative_namespace) . '/';
    if (!is_dir($root . '/' . $driver_directory)) {
      throw new \RuntimeException(sprintf("Cannot find the database driver namespace '%s' in module '%s'", $namespace, $module));
    }
    return $driver_directory;
  }

  /**
   * Gets database connection info as a URL.
   *
   * @param string $key
   *   (Optional) The database connection key.
   *
   * @return string
   *   The connection info as a URL.
   *
   * @throws \RuntimeException
   *   When the database connection is not defined.
   */
  public static function getConnectionInfoAsUrl($key = 'default') {
    $db_info = static::getConnectionInfo($key);
    if (empty($db_info) || empty($db_info['default'])) {
      throw new \RuntimeException("Database connection $key not defined or missing the 'default' settings");
    }
    $namespace = $db_info['default']['namespace'];

    // If the driver namespace is within a Drupal module, add the module name
    // to the connection options to make it easy for the connection class's
    // createUrlFromConnectionOptions() method to add it to the URL.
    if (static::isWithinModuleNamespace($namespace)) {
      $db_info['default']['module'] = explode('\\', $namespace)[1];
    }

    $connection_class = $namespace . '\\Connection';
    return $connection_class::createUrlFromConnectionOptions($db_info['default']);
  }

  /**
   * Checks whether a namespace is within the namespace of a Drupal module.
   *
   * This can be used to determine if a database driver's namespace is provided
   * by a Drupal module.
   *
   * @param string $namespace
   *   The namespace (for example, of a database driver) to check.
   *
   * @return bool
   *   TRUE if the passed in namespace is a sub-namespace of a Drupal module's
   *   namespace.
   *
   * @todo https://www.drupal.org/project/drupal/issues/3125476 Remove if we
   *   add this to the extension API or if
   *   \Drupal\Core\Database\Database::getConnectionInfoAsUrl() is removed.
   */
  private static function isWithinModuleNamespace(string $namespace) {
    [$first, $second] = explode('\\', $namespace, 3);

    // The namespace for Drupal modules is Drupal\MODULE_NAME, and the module
    // name must be all lowercase. Second-level namespaces containing uppercase
    // letters (e.g., "Core", "Component", "Driver") are not modules.
    // @see \Drupal\Core\DrupalKernel::getModuleNamespacesPsr4()
    // @see https://www.drupal.org/docs/8/creating-custom-modules/naming-and-placing-your-drupal-8-module#s-name-your-module
    return ($first === 'Drupal' && strtolower($second) === $second);
  }

}
