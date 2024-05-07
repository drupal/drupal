<?php

namespace Drupal\Core\Test;

use Drupal\Core\Database\Database;

/**
 * Provides a trait for shared test setup functionality.
 */
trait TestSetupTrait {

  /**
   * An array of config object names that are excluded from schema checking.
   *
   * @var string[]
   */
  protected static $configSchemaCheckerExclusions = [
    // Following are used to test lack of or partial schema. Where partial
    // schema is provided, that is explicitly tested in specific tests.
    'config_schema_test.no_schema',
    'config_schema_test.some_schema',
    'config_schema_test.schema_data_types',
    'config_schema_test.no_schema_data_types',
    // Used to test application of schema to filtering of configuration.
    'config_test.dynamic.system',
  ];

  /**
   * The dependency injection container used in the test.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The site directory of this test run.
   *
   * @var string
   */
  protected $siteDirectory = NULL;

  /**
   * The public file directory for the test environment.
   *
   * @see \Drupal\Tests\BrowserTestBase::prepareEnvironment()
   *
   * @var string
   */
  protected $publicFilesDirectory;

  /**
   * The site directory of the original parent site.
   *
   * @var string
   */
  protected $originalSite;

  /**
   * The private file directory for the test environment.
   *
   * @see \Drupal\Tests\BrowserTestBase::prepareEnvironment()
   *
   * @var string
   */
  protected $privateFilesDirectory;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The DrupalKernel instance used in the test.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected $kernel;

  /**
   * The database prefix of this test run.
   *
   * @var string
   */
  protected $databasePrefix;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The temporary file directory for the test environment.
   *
   * This value has to match the temporary directory created in
   * install_base_system() for test installs.
   *
   * @see \Drupal\Tests\BrowserTestBase::prepareEnvironment()
   * @see install_base_system()
   *
   * @var string
   */
  protected $tempFilesDirectory;

  /**
   * The test run ID.
   *
   * @var string
   */
  protected $testId;

  /**
   * Generates a database prefix for running tests.
   *
   * The database prefix is used by prepareEnvironment() to setup a public files
   * directory for the test to be run, which also contains the PHP error log,
   * which is written to in case of a fatal error. Since that directory is based
   * on the database prefix, all tests (even unit tests) need to have one, in
   * order to access and read the error log.
   *
   * The generated database table prefix is used for the Drupal installation
   * being performed for the test. It is also used as user agent HTTP header it
   * is also used in the user agent HTTP header value by BrowserTestBase, which
   * is sent to the Drupal installation of the test. During early Drupal all
   * bootstrap, the user agent HTTP header is parsed, and if it matches,
   * database queries use the database table prefix that has been generated
   * here.
   *
   * @see \Drupal\Tests\BrowserTestBase::prepareEnvironment()
   * @see drupal_valid_test_ua()
   */
  protected function prepareDatabasePrefix() {
    $test_db = new TestDatabase();
    $this->siteDirectory = $test_db->getTestSitePath();
    $this->databasePrefix = $test_db->getDatabasePrefix();
  }

  /**
   * Changes the database connection to the prefixed one.
   */
  protected function changeDatabasePrefix() {
    if (empty($this->databasePrefix)) {
      $this->prepareDatabasePrefix();
    }

    // If the test is run with argument dburl then use it.
    $db_url = getenv('SIMPLETEST_DB');
    if (!empty($db_url)) {
      // Ensure no existing database gets in the way. If a default database
      // exists already it must be removed.
      Database::removeConnection('default');
      $database = Database::convertDbUrlToConnectionInfo($db_url, $this->root ?? DRUPAL_ROOT, TRUE);
      Database::addConnectionInfo('default', 'default', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    if (is_null($connection_info)) {
      throw new \InvalidArgumentException('There is no database connection so no tests can be run. You must provide a SIMPLETEST_DB environment variable to run PHPUnit based functional tests outside of run-tests.sh.');
    }
    else {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        // Replace the full table prefix definition to ensure that no table
        // prefixes of the test runner leak into the test.
        $connection_info[$target]['prefix'] = $value['prefix'] . $this->databasePrefix;
      }
      Database::removeConnection('default');
      Database::addConnectionInfo('default', 'default', $connection_info['default']);
    }
  }

  /**
   * Gets the config schema exclusions for this test.
   *
   * @return string[]
   *   An array of config object names that are excluded from schema checking.
   */
  protected function getConfigSchemaExclusions() {
    $class = static::class;
    $exceptions = [];
    while ($class) {
      if (property_exists($class, 'configSchemaCheckerExclusions')) {
        $exceptions[] = $class::$configSchemaCheckerExclusions;
      }
      $class = get_parent_class($class);
    }
    // Filter out any duplicates.
    return array_unique(array_merge(...$exceptions));
  }

}
