<?php

/**
 * @file
 * Contains \Drupal\KernelTests\KernelTestBase.
 */

namespace Drupal\KernelTests;

use Drupal\Component\FileCache\ApcuFileCacheBackend;
use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Site\Settings;
use Drupal\simpletest\AssertContentTrait;
use Drupal\simpletest\AssertHelperTrait;
use Drupal\simpletest\RandomGeneratorTrait;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;

/**
 * Base class for functional integration tests.
 *
 * Tests extending this base class can access files and the database, but the
 * entire environment is initially empty. Drupal runs in a minimal mocked
 * environment, comparable to the one in the early installer.
 *
 * Unlike \Drupal\Tests\UnitTestCase, modules specified in the $modules
 * property are automatically added to the service container for each test.
 * The module/hook system is functional and operates on a fixed module list.
 * Additional modules needed in a test may be loaded and added to the fixed
 * module list.
 *
 * Unlike \Drupal\simpletest\WebTestBase, the modules are only loaded, but not
 * installed. Modules have to be installed manually, if needed.
 *
 * @see \Drupal\Tests\KernelTestBase::$modules
 * @see \Drupal\Tests\KernelTestBase::enableModules()
 *
 * @todo Extend ::setRequirementsFromAnnotation() and ::checkRequirements() to
 *   account for '@requires module'.
 */
abstract class KernelTestBase extends \PHPUnit_Framework_TestCase implements ServiceProviderInterface {

  use AssertLegacyTrait;
  use AssertContentTrait;
  use AssertHelperTrait;
  use RandomGeneratorTrait;

  /**
   * {@inheritdoc}
   *
   * Back up and restore any global variables that may be changed by tests.
   *
   * @see self::runTestInSeparateProcess
   */
  protected $backupGlobals = TRUE;

  /**
   * {@inheritdoc}
   *
   * Kernel tests are run in separate processes to prevent collisions between
   * code that may be loaded by tests.
   */
  protected $runTestInSeparateProcess = TRUE;

  /**
   * {@inheritdoc}
   *
   * Back up and restore static class properties that may be changed by tests.
   *
   * @see self::runTestInSeparateProcess
   */
  protected $backupStaticAttributes = TRUE;

  /**
   * {@inheritdoc}
   *
   * Contains a few static class properties for performance.
   */
  protected $backupStaticAttributesBlacklist = [
    // Ignore static discovery/parser caches to speed up tests.
    'Drupal\Component\Discovery\YamlDiscovery' => ['parsedFiles'],
    'Drupal\Core\DependencyInjection\YamlFileLoader' => ['yaml'],
    'Drupal\Core\Extension\ExtensionDiscovery' => ['files'],
    'Drupal\Core\Extension\InfoParser' => ['parsedInfos'],
    // Drupal::$container cannot be serialized.
    'Drupal' => ['container'],
    // Settings cannot be serialized.
    'Drupal\Core\Site\Settings' => ['instance'],
  ];

  /**
   * {@inheritdoc}
   *
   * Do not forward any global state from the parent process to the processes
   * that run the actual tests.
   *
   * @see self::runTestInSeparateProcess
   */
  protected $preserveGlobalState = FALSE;

  /**
   * @var \Composer\Autoload\Classloader
   */
  protected $classLoader;

  /**
   * @var string
   */
  protected $siteDirectory;

  /**
   * @var string
   */
  protected $databasePrefix;

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private static $initialContainerBuilder;

  /**
   * Modules to enable.
   *
   * Test classes extending this class, and any classes in the hierarchy up to
   * this class, may specify individual lists of modules to enable by setting
   * this property. The values of all properties in all classes in the class
   * hierarchy are merged.
   *
   * @see \Drupal\Tests\KernelTestBase::enableModules()
   * @see \Drupal\Tests\KernelTestBase::bootKernel()
   *
   * @var array
   */
  public static $modules = array();

  /**
   * The virtual filesystem root directory.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $vfsRoot;

  /**
   * @var int
   */
  protected $expectedLogSeverity;

  /**
   * @var string
   */
  protected $expectedLogMessage;

  /**
   * @todo Move into Config test base class.
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();

    // Change the current dir to DRUPAL_ROOT.
    chdir(static::getDrupalRoot());
  }

  /**
   * Returns the drupal root directory.
   *
   * @return string
   */
  protected static function getDrupalRoot() {
    return dirname(dirname(substr(__DIR__, 0, -strlen(__NAMESPACE__))));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->root = static::getDrupalRoot();
    $this->initFileCache();
    $this->bootEnvironment();
    $this->bootKernel();
  }

  /**
   * Bootstraps a basic test environment.
   *
   * Should not be called by tests. Only visible for DrupalKernel integration
   * tests.
   *
   * @see \Drupal\system\Tests\DrupalKernel\DrupalKernelTest
   * @internal
   */
  protected function bootEnvironment() {
    $this->streamWrappers = array();
    \Drupal::unsetContainer();

    // @see /core/tests/bootstrap.php
    $this->classLoader = $GLOBALS['loader'];

    require_once $this->root . '/core/includes/bootstrap.inc';

    // Set up virtual filesystem.
    // Ensure that the generated test site directory does not exist already,
    // which may happen with a large amount of concurrent threads and
    // long-running tests.
    do {
      $suffix = mt_rand(100000, 999999);
      $this->siteDirectory = 'sites/simpletest/' . $suffix;
      $this->databasePrefix = 'simpletest' . $suffix;
    } while (is_dir($this->root . '/' . $this->siteDirectory));

    $this->vfsRoot = vfsStream::setup('root', NULL, array(
      'sites' => array(
        'simpletest' => array(
          $suffix => array(),
        ),
      ),
    ));
    $this->siteDirectory = vfsStream::url('root/sites/simpletest/' . $suffix);

    mkdir($this->siteDirectory . '/files', 0775);
    mkdir($this->siteDirectory . '/files/config/' . CONFIG_STAGING_DIRECTORY, 0775, TRUE);

    // Ensure that all code that relies on drupal_valid_test_ua() can still be
    // safely executed. This primarily affects the (test) site directory
    // resolution (used by e.g. LocalStream and PhpStorage).
    $this->databasePrefix = 'simpletest' . $suffix;
    drupal_valid_test_ua($this->databasePrefix);

    $settings = array(
      'hash_salt' => get_class($this),
      'file_public_path' => $this->siteDirectory . '/files',
      // Disable Twig template caching/dumping.
      'twig_cache' => FALSE,
      // @see \Drupal\KernelTests\KernelTestBase::register()
    );
    new Settings($settings);

    $GLOBALS['config_directories'] = array(
      CONFIG_STAGING_DIRECTORY => $this->siteDirectory . '/files/config/staging',
    );

    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }
    Database::addConnectionInfo('default', 'default', $this->getDatabaseConnectionInfo()['default']);
  }

  /**
   * Bootstraps a kernel for a test.
   */
  private function bootKernel() {
    $this->setSetting('container_yamls', []);
    // Allow for test-specific overrides.
    $settings_services_file = $this->root . '/sites/default' . '/testing.services.yml';
    if (file_exists($settings_services_file)) {
      // Copy the testing-specific service overrides in place.
      $testing_services_file = $this->root . '/' . $this->siteDirectory . '/services.yml';
      copy($settings_services_file, $testing_services_file);
      $this->setSetting('container_yamls', [$testing_services_file]);
    }

    // Allow for global test environment overrides.
    if (file_exists($test_env = $this->root . '/sites/default/testing.services.yml')) {
      $GLOBALS['conf']['container_yamls']['testing'] = $test_env;
    }
    // Add this test class as a service provider.
    $GLOBALS['conf']['container_service_providers']['test'] = $this;

    $modules = self::getModulesToEnable(get_class($this));

    // Prepare a precompiled container for all tests of this class.
    // Substantially improves performance, since ContainerBuilder::compile()
    // is very expensive. Encourages testing best practices (small tests).
    // Normally a setUpBeforeClass() operation, but object scope is required to
    // inject $this test class instance as a service provider (see above).
    $rc = new \ReflectionClass(get_class($this));
    $test_method_count = count(array_filter($rc->getMethods(), function ($method) {
      // PHPUnit's @test annotations are intentionally ignored/not supported.
      return strpos($method->getName(), 'test') === 0;
    }));
    if ($test_method_count > 1 && !$this->isTestInIsolation()) {
      // Clone a precompiled, empty ContainerBuilder instance for each test.
      $container = $this->getCompiledContainerBuilder($modules);
    }

    // Bootstrap the kernel. Do not use createFromRequest() to retain Settings.
    $kernel = new DrupalKernel('testing', $this->classLoader, FALSE);
    $kernel->setSitePath($this->siteDirectory);
    // Boot the precompiled container. The kernel will enhance it with synthetic
    // services.
    if (isset($container)) {
      $kernel->setContainer($container);
      unset($container);
    }
    // Boot a new one-time container from scratch. Ensure to set the module list
    // upfront to avoid a subsequent rebuild.
    elseif ($modules && $extensions = $this->getExtensionsForModules($modules)) {
      $kernel->updateModules($extensions, $extensions);
    }
    // DrupalKernel::boot() is not sufficient as it does not invoke preHandle(),
    // which is required to initialize legacy global variables.
    $request = Request::create('/');
    $kernel->prepareLegacyRequest($request);

    // register() is only called if a new container was built/compiled.
    $this->container = $kernel->getContainer();

    // Ensure database tasks have been run.
    require_once __DIR__ . '/../../../includes/install.inc';
    $connection = Database::getConnection();
    $errors = db_installer_object($connection->driver())->runTasks();
    if (!empty($errors)) {
      $this->fail('Failed to run installer database tasks: ' . implode(', ', $errors));
    }

    if ($modules) {
      $this->container->get('module_handler')->loadAll();
    }

    // Write the core.extension configuration.
    // Required for ConfigInstaller::installDefaultConfig() to work.
    $this->container->get('config.storage')->write('core.extension', array(
      'module' => array_fill_keys($modules, 0),
      'theme' => array(),
    ));

    $settings = Settings::getAll();
    $settings['php_storage']['default'] = [
      'class' => '\Drupal\Component\PhpStorage\FileStorage',
    ];
    new Settings($settings);
  }

  /**
   * Configuration accessor for tests. Returns non-overridden configuration.
   *
   * @param string $name
   *   The configuration name.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration object with original configuration data.
   */
  protected function config($name) {
    return $this->container->get('config.factory')->getEditable($name);
  }

  /**
   * Returns the Database connection info to be used for this test.
   *
   * This method only exists for tests of the Database component itself, because
   * they require multiple database connections. Each SQLite :memory: connection
   * creates a new/separate database in memory. A shared-memory SQLite file URI
   * triggers PHP open_basedir/allow_url_fopen/allow_url_include restrictions.
   * Due to that, Database tests are running against a SQLite database that is
   * located in an actual file in the system's temporary directory.
   *
   * Other tests should not override this method.
   *
   * @return array
   *   A Database connection info array.
   *
   * @internal
   */
  protected function getDatabaseConnectionInfo() {
    // If the test is run with argument dburl then use it.
    $db_url = getenv('SIMPLETEST_DB');
    if (!empty($db_url)) {
      $database = Database::convertDbUrlToConnectionInfo($db_url, $this->root);
      Database::addConnectionInfo('default', 'default', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    if (is_null($connection_info)) {
      throw new \InvalidArgumentException('There is no database connection so no tests can be run. You must provide a SIMPLETEST_DB environment variable, like "sqlite://localhost//tmp/test.sqlite", to run PHPUnit based functional tests outside of run-tests.sh.');
    }
    else {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        // Replace the full table prefix definition to ensure that no table
        // prefixes of the test runner leak into the test.
        $connection_info[$target]['prefix'] = array(
          'default' => $value['prefix']['default'] . $this->databasePrefix,
        );
      }
    }
    return $connection_info;
  }

  /**
   * Prepares a precompiled ContainerBuilder for all tests of this class.
   *
   * Avoids repetitive calls to ContainerBuilder::compile(), which is very slow.
   *
   * Based on the (always identical) list of $modules to enable, an initial
   * container is compiled, all instantiated services are reset/removed, and
   * this precompiled container is stored in a static class property. (Static,
   * because PHPUnit instantiates a new class instance for each test *method*.)
   *
   * This method is not invoked if there is only a single test method. It is
   * also not invoked for tests running in process isolation (since each test
   * method runs in a separate process).
   *
   * The ContainerBuilder is not dumped into the filesystem (which would yield
   * an actually compiled Container class), because
   *
   * 1. PHP code cannot be unloaded, so e.g. 900 tests would load 900 different,
   *    full Container classes into memory, quickly exceeding any sensible
   *    memory consumption (GigaBytes).
   * 2. Dumping a Container class requires to actually write to the system's
   *    temporary directory. This is not really easy with vfs, because vfs
   *    doesn't support yet "include 'vfs://container.php'.". Maybe we could fix
   *    that upstream.
   * 3. PhpDumper is very slow on its own.
   *
   * @param string[] $modules
   *   The list of modules to enable.
   *
   * @return \Drupal\Core\DependencyInjection\ContainerBuilder
   *   A clone of the precompiled, empty service container.
   */
  private function getCompiledContainerBuilder(array $modules) {
    if (!isset(self::$initialContainerBuilder)) {
      $kernel = new DrupalKernel('testing', $this->classLoader, FALSE);
      $kernel->setSitePath($this->siteDirectory);
      if ($modules && $extensions = $this->getExtensionsForModules($modules)) {
        $kernel->updateModules($extensions, $extensions);
      }
      $kernel->boot();
      self::$initialContainerBuilder = $kernel->getContainer();

      // Remove all instantiated services, so the container is safe for cloning.
      // Technically, ContainerBuilder::set($id, NULL) removes each definition,
      // but the container is compiled/frozen already.
      foreach (self::$initialContainerBuilder->getServiceIds() as $id) {
        self::$initialContainerBuilder->set($id, NULL);
      }

      // Destruct and trigger garbage collection.
      \Drupal::unsetContainer();
      $kernel->shutdown();
      $kernel = NULL;
      // @see register()
      $this->container = NULL;
    }

    $container = clone self::$initialContainerBuilder;

    return $container;
  }

  /**
   * Initializes the FileCache component.
   *
   * We can not use the Settings object in a component, that's why we have to do
   * it here instead of \Drupal\Component\FileCache\FileCacheFactory.
   */
  protected function initFileCache() {
    $configuration = Settings::get('file_cache');

    // Provide a default configuration, if not set.
    if (!isset($configuration['default'])) {
      $configuration['default'] = [
        'class' => FileCache::class,
        'cache_backend_class' => NULL,
        'cache_backend_configuration' => [],
      ];
      // @todo Use extension_loaded('apcu') for non-testbot
      //  https://www.drupal.org/node/2447753.
      if (function_exists('apc_fetch')) {
        $configuration['default']['cache_backend_class'] = ApcuFileCacheBackend::class;
      }
    }
    FileCacheFactory::setConfiguration($configuration);
    FileCacheFactory::setPrefix(Settings::getApcuPrefix('file_cache', $this->root));
  }

  /**
   * Returns Extension objects for $modules to enable.
   *
   * @param string[] $modules
   *   The list of modules to enable.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Extension objects for $modules, keyed by module name.
   *
   * @throws \PHPUnit_Framework_Exception
   *   If a module is not available.
   *
   * @see \Drupal\Tests\KernelTestBase::enableModules()
   * @see \Drupal\Core\Extension\ModuleHandler::add()
   */
  private function getExtensionsForModules(array $modules) {
    $extensions = array();
    $discovery = new ExtensionDiscovery($this->root);
    $discovery->setProfileDirectories(array());
    $list = $discovery->scan('module');
    foreach ($modules as $name) {
      if (!isset($list[$name])) {
        throw new \PHPUnit_Framework_Exception("Unavailable module: '$name'. If this module needs to be downloaded separately, annotate the test class with '@requires module $name'.");
      }
      $extensions[$name] = $list[$name];
    }
    return $extensions;
  }

  /**
   * Registers test-specific services.
   *
   * Extend this method in your test to register additional services. This
   * method is called whenever the kernel is rebuilt.
   *
   * @param \Drupal\Core\DependencyInjection\ContainerBuilder $container
   *   The service container to enhance.
   *
   * @see \Drupal\Tests\KernelTestBase::bootKernel()
   */
  public function register(ContainerBuilder $container) {
    // Keep the container object around for tests.
    $this->container = $container;

    $container
      ->register('flood', 'Drupal\Core\Flood\MemoryBackend')
      ->addArgument(new Reference('request_stack'));
    $container
      ->register('lock', 'Drupal\Core\Lock\NullLockBackend');
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory');
    $container
      ->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory')
      // Must persist container rebuilds, or all data would vanish otherwise.
      ->addTag('persist');
    $container
      ->setAlias('keyvalue', 'keyvalue.memory');

    if ($this->strictConfigSchema) {
      $container
        ->register('simpletest.config_schema_checker', 'Drupal\Core\Config\Testing\ConfigSchemaChecker')
        ->addArgument(new Reference('config.typed'))
        ->addTag('event_subscriber');
    }

    if ($container->hasDefinition('path_processor_alias')) {
      // Prevent the alias-based path processor, which requires a url_alias db
      // table, from being registered to the path processor manager. We do this
      // by removing the tags that the compiler pass looks for. This means the
      // url generator can safely be used within tests.
      $container->getDefinition('path_processor_alias')
        ->clearTag('path_processor_inbound')
        ->clearTag('path_processor_outbound');
    }

    if ($container->hasDefinition('password')) {
      $container->getDefinition('password')
        ->setArguments(array(1));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function assertPostConditions() {
    // Execute registered Drupal shutdown functions prior to tearing down.
    // @see _drupal_shutdown_function()
    $callbacks = &drupal_register_shutdown_function();
    while ($callback = array_shift($callbacks)) {
      call_user_func_array($callback['callback'], $callback['arguments']);
    }

    // Shut down the kernel (if bootKernel() was called).
    // @see \Drupal\system\Tests\DrupalKernel\DrupalKernelTest
    if ($this->container) {
      $this->container->get('kernel')->shutdown();
    }

    // Fail in case any (new) shutdown functions exist.
    $this->assertCount(0, drupal_register_shutdown_function(), 'Unexpected Drupal shutdown callbacks exist after running shutdown functions.');

    parent::assertPostConditions();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->kernel->shutdown();
    }

    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_default');
    $original_prefix = $original_connection_info['default']['prefix']['default'];
    $test_connection_info = Database::getConnectionInfo('default');
    $test_prefix = $test_connection_info['default']['prefix']['default'];
    if ($original_prefix != $test_prefix) {
      $tables = Database::getConnection()->schema()->findTables('%');
      foreach ($tables as $table) {
        if (Database::getConnection()->schema()->dropTable($table)) {
          unset($tables[$table]);
        }
      }
    }

    // Free up memory: Own properties.
    $this->classLoader = NULL;
    $this->vfsRoot = NULL;
    $this->configImporter = NULL;

    // Free up memory: Custom test class properties.
    // Note: Private properties cannot be cleaned up.
    $rc = new \ReflectionClass(__CLASS__);
    $blacklist = array();
    foreach ($rc->getProperties() as $property) {
      $blacklist[$property->name] = $property->getDeclaringClass()->name;
    }
    $rc = new \ReflectionClass($this);
    foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
      if (!$property->isStatic() && !isset($blacklist[$property->name])) {
        $this->{$property->name} = NULL;
      }
    }

    // Clean FileCache cache.
    FileCache::reset();

    // Clean up statics, container, and settings.
    if (function_exists('drupal_static_reset')) {
      drupal_static_reset();
    }
    \Drupal::unsetContainer();
    $this->container = NULL;
    new Settings(array());

    parent::tearDown();
  }

  /**
   * @after
   *
   * Additional tear down method to close the connection at the end.
   */
  public function tearDownCloseDatabaseConnection() {
    // Destroy the database connection, which for example removes the memory
    // from sqlite in memory.
    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass() {
    // Free up memory: Precompiled container.
    self::$initialContainerBuilder = NULL;
    parent::tearDownAfterClass();
  }

  /**
   * Installs default configuration for a given list of modules.
   *
   * @param string|string[] $modules
   *   A list of modules for which to install default configuration.
   *
   * @throws \LogicException
   *   If any module in $modules is not enabled.
   */
  protected function installConfig($modules) {
    foreach ((array) $modules as $module) {
      if (!$this->container->get('module_handler')->moduleExists($module)) {
        throw new \LogicException("$module module is not enabled.");
      }
      $this->container->get('config.installer')->installDefaultConfig('module', $module);
    }
  }

  /**
   * Installs database tables from a module schema definition.
   *
   * @param string $module
   *   The name of the module that defines the table's schema.
   * @param string|array $tables
   *   The name or an array of the names of the tables to install.
   *
   * @throws \LogicException
   *   If $module is not enabled or the table schema cannot be found.
   */
  protected function installSchema($module, $tables) {
    // drupal_get_module_schema() is technically able to install a schema
    // of a non-enabled module, but its ability to load the module's .install
    // file depends on many other factors. To prevent differences in test
    // behavior and non-reproducible test failures, we only allow the schema of
    // explicitly loaded/enabled modules to be installed.
    if (!$this->container->get('module_handler')->moduleExists($module)) {
      throw new \LogicException("$module module is not enabled.");
    }
    $tables = (array) $tables;
    foreach ($tables as $table) {
      $schema = drupal_get_module_schema($module, $table);
      if (empty($schema)) {
        throw new \LogicException("$module module does not define a schema for table '$table'.");
      }
      $this->container->get('database')->schema()->createTable($table, $schema);
    }
  }

  /**
   * Installs the storage schema for a specific entity type.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function installEntitySchema($entity_type_id) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $this->container->get('entity.manager');
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $entity_manager->onEntityTypeCreate($entity_type);

    // For test runs, the most common storage backend is a SQL database. For
    // this case, ensure the tables got created.
    $storage = $entity_manager->getStorage($entity_type_id);
    if ($storage instanceof SqlEntityStorageInterface) {
      $tables = $storage->getTableMapping()->getTableNames();
      $db_schema = $this->container->get('database')->schema();
      $all_tables_exist = TRUE;
      foreach ($tables as $table) {
        if (!$db_schema->tableExists($table)) {
          $this->fail(SafeMarkup::format('Installed entity type table for the %entity_type entity type: %table', array(
            '%entity_type' => $entity_type_id,
            '%table' => $table,
          )));
          $all_tables_exist = FALSE;
        }
      }
      if ($all_tables_exist) {
        $this->pass(SafeMarkup::format('Installed entity type tables for the %entity_type entity type: %tables', array(
          '%entity_type' => $entity_type_id,
          '%tables' => '{' . implode('}, {', $tables) . '}',
        )));
      }
    }
  }

  /**
   * Enables modules for this test.
   *
   * @param string[] $modules
   *   A list of modules to enable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified individually. The modules are only
   *   added to the active module list and loaded; i.e., their database schema
   *   is not installed. hook_install() is not invoked. A custom module weight
   *   is not applied.
   *
   * @throws \LogicException
   *   If any module in $modules is already enabled.
   * @throws \RuntimeException
   *   If a module is not enabled after enabling it.
   */
  protected function enableModules(array $modules) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    if ($trace[1]['function'] === 'setUp') {
      trigger_error('KernelTestBase::enableModules() should not be called from setUp(). Use the $modules property instead.', E_USER_DEPRECATED);
    }
    unset($trace);

    // Perform an ExtensionDiscovery scan as this function may receive a
    // profile that is not the current profile, and we don't yet have a cached
    // way to receive inactive profile information.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $listing = new ExtensionDiscovery(\Drupal::root());
    $module_list = $listing->scan('module');
    // In ModuleHandlerTest we pass in a profile as if it were a module.
    $module_list += $listing->scan('profile');

    // Set the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');

    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    $active_storage = $this->container->get('config.storage');
    $extension_config = $active_storage->read('core.extension');

    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        throw new \LogicException("$module module is already enabled.");
      }
      $module_handler->addModule($module, $module_list[$module]->getPath());
      // Maintain the list of enabled modules in configuration.
      $extension_config['module'][$module] = 0;
    }
    $active_storage->write('core.extension', $extension_config);

    // Update the kernel to make their services available.
    $extensions = $module_handler->getModuleList();
    $this->container->get('kernel')->updateModules($extensions, $extensions);

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if (!$module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not enabled after enabling it.");
      }
    }
  }

  /**
   * Disables modules for this test.
   *
   * @param string[] $modules
   *   A list of modules to disable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified with dependent modules first.
   *   Code of previously enabled modules is still loaded. The modules are only
   *   removed from the active module list.
   *
   * @throws \LogicException
   *   If any module in $modules is already disabled.
   * @throws \RuntimeException
   *   If a module is not disabled after disabling it.
   */
  protected function disableModules(array $modules) {
    // Unset the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');
    $module_filenames = $module_handler->getModuleList();
    $extension_config = $this->config('core.extension');
    foreach ($modules as $module) {
      if (!$module_handler->moduleExists($module)) {
        throw new \LogicException("$module module cannot be disabled because it is not enabled.");
      }
      unset($module_filenames[$module]);
      $extension_config->clear('module.' . $module);
    }
    $extension_config->save();
    $module_handler->setModuleList($module_filenames);
    $module_handler->resetImplementations();
    // Update the kernel to remove their services.
    $this->container->get('kernel')->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make _theme() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not disabled after disabling it.");
      }
    }
  }

  /**
   * Renders a render array.
   *
   * @param array $elements
   *   The elements to render.
   *
   * @return string
   *   The rendered string output (typically HTML).
   */
  protected function render(array &$elements) {
    // Use the bare HTML page renderer to render our links.
    $renderer = $this->container->get('bare_html_page_renderer');
    $response = $renderer->renderBarePage(
      $build, '', $this->container->get('theme.manager')->getActiveTheme()->getName()
    );

    // Glean the content from the response object.
    $this->setRawContent($response->getContent());
    $this->verbose('<pre style="white-space: pre-wrap">' . Html::escape($content));
    return $content;
  }

  /**
   * Sets an in-memory Settings variable.
   *
   * @param string $name
   *   The name of the setting to set.
   * @param bool|string|int|array|null $value
   *   The value to set. Note that array values are replaced entirely; use
   *   \Drupal\Core\Site\Settings::get() to perform custom merges.
   */
  protected function setSetting($name, $value) {
    $settings = Settings::getAll();
    $settings[$name] = $value;
    new Settings($settings);
  }

  /**
   * Returns a ConfigImporter object to import test configuration.
   *
   * @return \Drupal\Core\Config\ConfigImporter
   *
   * @todo Move into Config-specific test base class.
   */
  protected function configImporter() {
    if (!$this->configImporter) {
      // Set up the ConfigImporter object for testing.
      $storage_comparer = new StorageComparer(
        $this->container->get('config.storage.staging'),
        $this->container->get('config.storage'),
        $this->container->get('config.manager')
      );
      $this->configImporter = new ConfigImporter(
        $storage_comparer,
        $this->container->get('event_dispatcher'),
        $this->container->get('config.manager'),
        $this->container->get('lock'),
        $this->container->get('config.typed'),
        $this->container->get('module_handler'),
        $this->container->get('module_installer'),
        $this->container->get('theme_handler'),
        $this->container->get('string_translation')
      );
    }
    // Always recalculate the changelist when called.
    return $this->configImporter->reset();
  }

  /**
   * Copies configuration objects from a source storage to a target storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source config storage.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target config storage.
   *
   * @todo Move into Config-specific test base class.
   */
  protected function copyConfig(StorageInterface $source_storage, StorageInterface $target_storage) {
    $target_storage->deleteAll();
    foreach ($source_storage->listAll() as $name) {
      $target_storage->write($name, $source_storage->read($name));
    }
  }

  /**
   * Stops test execution.
   */
  protected function stop() {
    $this->getTestResultObject()->stop();
  }

  /**
   * Dumps the current state of the virtual filesystem to STDOUT.
   */
  protected function vfsDump() {
    vfsStream::inspect(new vfsStreamPrintVisitor());
  }

  /**
   * Returns the modules to enable for this test.
   *
   * @param string $class
   *   The fully-qualified class name of this test.
   *
   * @return array
   */
  private static function getModulesToEnable($class) {
    $modules = array();
    while ($class) {
      if (property_exists($class, 'modules')) {
        // Only add the modules, if the $modules property was not inherited.
        $rp = new \ReflectionProperty($class, 'modules');
        if ($rp->class == $class) {
          $modules[$class] = $class::$modules;
        }
      }
      $class = get_parent_class($class);
    }
    // Modules have been collected in reverse class hierarchy order; modules
    // defined by base classes should be sorted first. Then, merge the results
    // together.
    $modules = array_reverse($modules);
    return call_user_func_array('array_merge_recursive', $modules);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTemplate(\Text_Template $template) {
    $bootstrap_globals = '';

    // Fix missing bootstrap.php when $preserveGlobalState is FALSE.
    // @see https://github.com/sebastianbergmann/phpunit/pull/797
    $bootstrap_globals .= '$__PHPUNIT_BOOTSTRAP = ' . var_export($GLOBALS['__PHPUNIT_BOOTSTRAP'], TRUE) . ";\n";

    // Avoid repetitive test namespace discoveries to improve performance.
    // @see /core/tests/bootstrap.php
    $bootstrap_globals .= '$namespaces = ' . var_export($GLOBALS['namespaces'], TRUE) . ";\n";

    $template->setVar(array(
      'constants' => '',
      'included_files' => '',
      'globals' => $bootstrap_globals,
    ));
  }

  /**
   * Returns whether the current test runs in isolation.
   *
   * @return bool
   *
   * @see https://github.com/sebastianbergmann/phpunit/pull/1350
   */
  protected function isTestInIsolation() {
    return function_exists('__phpunit_run_isolated_test');
  }

  /**
   * BC: Automatically resolve former KernelTestBase class properties.
   *
   * Test authors should follow the provided instructions and adjust their tests
   * accordingly.
   *
   * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.2.0.
   */
  public function __get($name) {
    if (in_array($name, array(
      'public_files_directory',
      'private_files_directory',
      'temp_files_directory',
      'translation_files_directory',
    ))) {
      // @comment it in again.
      trigger_error(sprintf("KernelTestBase::\$%s no longer exists. Use the regular API method to retrieve it instead (e.g., Settings).", $name), E_USER_DEPRECATED);
      switch ($name) {
        case 'public_files_directory':
          return Settings::get('file_public_path', conf_path() . '/files');

        case 'private_files_directory':
          return $this->container->get('config.factory')->get('system.file')->get('path.private');

        case 'temp_files_directory':
          return file_directory_temp();

        case 'translation_files_directory':
          return Settings::get('file_public_path', conf_path() . '/translations');
      }
    }

    if ($name === 'configDirectories') {
      trigger_error(sprintf("KernelTestBase::\$%s no longer exists. Use config_get_config_directory() directly instead.", $name), E_USER_DEPRECATED);
      return array(
        CONFIG_STAGING_DIRECTORY => config_get_config_directory(CONFIG_STAGING_DIRECTORY),
      );
    }

    $denied = array(
      // @see \Drupal\simpletest\TestBase
      'testId',
      'timeLimit',
      'results',
      'assertions',
      'skipClasses',
      'verbose',
      'verboseId',
      'verboseClassName',
      'verboseDirectory',
      'verboseDirectoryUrl',
      'dieOnFail',
      'kernel',
      // @see \Drupal\simpletest\TestBase::prepareEnvironment()
      'generatedTestFiles',
      // @see \Drupal\simpletest\KernelTestBase::containerBuild()
      'keyValueFactory',
    );
    if (in_array($name, $denied) || strpos($name, 'original') === 0) {
      throw new \RuntimeException(sprintf('TestBase::$%s property no longer exists', $name));
    }
  }

}
