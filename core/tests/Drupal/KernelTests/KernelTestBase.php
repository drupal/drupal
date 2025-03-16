<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Component\FileCache\ApcuFileCacheBackend;
use Drupal\Component\FileCache\FileCache;
use Drupal\Component\FileCache\FileCacheFactory;
use Drupal\Core\Config\Development\ConfigSchemaChecker;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Core\Test\EventSubscriber\FieldStorageCreateCheckSubscriber;
use Drupal\Core\Test\TestDatabase;
use Drupal\Tests\ConfigTestTrait;
use Drupal\Tests\ExtensionListTestTrait;
use Drupal\Tests\RandomGeneratorTrait;
use Drupal\Tests\PhpUnitCompatibilityTrait;
use Drupal\Tests\TestRequirementsTrait;
use Drupal\TestTools\Comparator\MarkupInterfaceComparator;
use Drupal\TestTools\Extension\DeprecationBridge\ExpectDeprecationTrait;
use Drupal\TestTools\Extension\Dump\DebugDump;
use Drupal\TestTools\Extension\SchemaInspector;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\visitor\vfsStreamPrintVisitor;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Base class for functional integration tests.
 *
 * Module tests extending KernelTestBase must exist in the
 * Drupal\Tests\your_module\Kernel namespace and live in the
 * modules/your_module/tests/src/Kernel directory.
 *
 * Tests for core/lib/Drupal classes extending KernelTestBase must exist in the
 * \Drupal\KernelTests\Core namespace and live in the
 * core/tests/Drupal/KernelTests directory.
 *
 * This base class should be useful for testing some types of integrations which
 * don't require the overhead of a fully-installed Drupal instance, but which
 * have many dependencies on parts of Drupal which can't or shouldn't be mocked.
 *
 * This base class partially boots a fixture Drupal. The state of the fixture
 * Drupal is comparable to the state of a system during the early part of the
 * installation process.
 *
 * Tests extending this base class can access services and the database, but the
 * system is initially empty. This Drupal runs in a minimal mocked filesystem
 * which operates within vfsStream.
 *
 * Modules specified in the $modules property are added to the service container
 * for each test. The module/hook system is functional. Additional modules
 * needed in a test should override $modules. Modules specified in this way will
 * be added to those specified in superclasses.
 *
 * Unlike \Drupal\Tests\BrowserTestBase, the modules are not installed. They are
 * loaded such that their services and hooks are available, but the install
 * process has not been performed.
 *
 * Other modules can be made available in this way using
 * KernelTestBase::enableModules().
 *
 * Some modules can be brought into a fully-installed state using
 * KernelTestBase::installConfig(), KernelTestBase::installSchema(), and
 * KernelTestBase::installEntitySchema(). Alternately, tests which need modules
 * to be fully installed could inherit from \Drupal\Tests\BrowserTestBase.
 *
 * Using Symfony's dump() function in Kernel tests will produce output on the
 * command line, whether the call to dump() is in test code or site code.
 *
 * @see \Drupal\KernelTests\KernelTestBase::$modules
 * @see \Drupal\KernelTests\KernelTestBase::enableModules()
 * @see \Drupal\KernelTests\KernelTestBase::installConfig()
 * @see \Drupal\KernelTests\KernelTestBase::installEntitySchema()
 * @see \Drupal\KernelTests\KernelTestBase::installSchema()
 * @see \Drupal\Tests\BrowserTestBase
 *
 * @ingroup testing
 */
abstract class KernelTestBase extends TestCase implements ServiceProviderInterface {

  use AssertContentTrait;
  use RandomGeneratorTrait;
  use ConfigTestTrait;
  use ExtensionListTestTrait;
  use TestRequirementsTrait;
  use PhpUnitCompatibilityTrait;
  use ProphecyTrait;
  use ExpectDeprecationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $name) {
    parent::__construct($name);
    $this->setRunTestInSeparateProcess(TRUE);
  }

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
   * Modules to install.
   *
   * The test runner will merge the $modules lists from this class, the class
   * it extends, and so on up the class hierarchy. It is not necessary to
   * include modules in your list that a parent class has already declared.
   *
   * @var array
   *
   * @see \Drupal\KernelTests\KernelTestBase::enableModules()
   * @see \Drupal\KernelTests\KernelTestBase::bootKernel()
   */
  protected static $modules = [];

  /**
   * The virtual filesystem root directory.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $vfsRoot;

  /**
   * @var \Drupal\Core\Config\ConfigImporter
   *
   * @todo Move into Config test base class.
   */
  protected $configImporter;

  /**
   * The key_value service that must persist between container rebuilds.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueMemoryFactory
   */
  protected KeyValueMemoryFactory $keyValue;

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = TRUE;

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
   * Set to TRUE to make user 1 a super user.
   *
   * @var bool
   *
   * @see \Drupal\Core\Session\SuperUserAccessPolicy
   */
  protected bool $usesSuperUserAccessPolicy;

  /**
   * Registers the dumper CLI handler when the DebugDump extension is enabled.
   */
  #[BeforeClass]
  public static function setDebugDumpHandler(): void {
    if (DebugDump::isEnabled()) {
      VarDumper::setHandler(DebugDump::class . '::cliHandler');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow tests to compare MarkupInterface objects via assertEquals().
    $this->registerComparator(new MarkupInterfaceComparator());

    $this->root = static::getDrupalRoot();
    chdir($this->root);
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
   * @see \Drupal\KernelTests\Core\DrupalKernel\DrupalKernelTest
   * @internal
   */
  protected function bootEnvironment() {
    \Drupal::unsetContainer();

    $this->classLoader = require $this->root . '/autoload.php';

    // Set up virtual filesystem.
    Database::addConnectionInfo('default', 'test-runner', $this->getDatabaseConnectionInfo()['default']);
    $test_db = new TestDatabase();
    $this->siteDirectory = $test_db->getTestSitePath();

    // Ensure that all code that relies on drupal_valid_test_ua() can still be
    // safely executed. This primarily affects the (test) site directory
    // resolution (used by e.g. LocalStream and PhpStorage).
    $this->databasePrefix = $test_db->getDatabasePrefix();
    drupal_valid_test_ua($this->databasePrefix);

    $settings = [
      'hash_salt' => static::class,
      'file_public_path' => $this->siteDirectory . '/files',
      // Disable Twig template caching/dumping.
      'twig_cache' => FALSE,
      // @see \Drupal\KernelTests\KernelTestBase::register()
    ];
    new Settings($settings);

    $this->setUpFilesystem();

    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }
    Database::addConnectionInfo('default', 'default', $this->getDatabaseConnectionInfo()['default']);
  }

  /**
   * Sets up the filesystem, so things like the file directory.
   */
  protected function setUpFilesystem() {
    $test_db = new TestDatabase($this->databasePrefix);
    $test_site_path = $test_db->getTestSitePath();

    $this->vfsRoot = vfsStream::setup('root');
    $this->vfsRoot->addChild(vfsStream::newDirectory($test_site_path));
    $this->siteDirectory = vfsStream::url('root/' . $test_site_path);

    mkdir($this->siteDirectory . '/files', 0775);
    mkdir($this->siteDirectory . '/files/config/sync', 0775, TRUE);

    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings['file_public_path'] = $this->siteDirectory . '/files';
    $settings['config_sync_directory'] = $this->siteDirectory . '/files/config/sync';
    new Settings($settings);
  }

  /**
   * @return string
   *   The database prefix string used to isolate test database tables.
   */
  public function getDatabasePrefix() {
    return $this->databasePrefix;
  }

  /**
   * Bootstraps a kernel for a test.
   */
  protected function bootKernel() {
    $this->setSetting('container_yamls', []);
    // Allow for test-specific overrides.
    $settings_services_file = $this->root . '/sites/default/testing.services.yml';
    if (file_exists($settings_services_file)) {
      // Copy the testing-specific service overrides in place.
      $testing_services_file = $this->siteDirectory . '/services.yml';
      copy($settings_services_file, $testing_services_file);
      $this->setSetting('container_yamls', [$testing_services_file]);
    }

    // Allow for global test environment overrides.
    if (file_exists($test_env = $this->root . '/sites/default/testing.services.yml')) {
      $GLOBALS['conf']['container_yamls']['testing'] = $test_env;
    }
    // Add this test class as a service provider.
    $GLOBALS['conf']['container_service_providers']['test'] = $this;

    $modules = self::getModulesToEnable(static::class);

    // When a module is providing the database driver, then enable that module.
    $connection_info = Database::getConnectionInfo();
    $namespace = $connection_info['default']['namespace'] ?? '';
    $autoload = $connection_info['default']['autoload'] ?? '';
    if (str_contains($autoload, 'src/Driver/Database/')) {
      [$first, $second] = explode('\\', $namespace, 3);
      if ($first === 'Drupal' && strtolower($second) === $second) {
        // Add the module that provides the database driver to the list of
        // modules as the first to be enabled.
        array_unshift($modules, $second);
      }
    }

    // Bootstrap the kernel. Do not use createFromRequest() to retain Settings.
    $kernel = new DrupalKernel('testing', $this->classLoader, FALSE);
    $kernel->setSitePath($this->siteDirectory);
    // Boot a new one-time container from scratch. Set the module list upfront
    // to avoid a subsequent rebuild or setting the kernel into the
    // pre-installer mode.
    $extensions = $modules ? $this->getExtensionsForModules($modules) : [];
    $kernel->updateModules($extensions, $extensions);

    // DrupalKernel::boot() is not sufficient as it does not invoke preHandle(),
    // which is required to initialize legacy global variables.
    $request = Request::create('/');
    $kernel->boot();
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route('<none>'));
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, '<none>');
    $kernel->preHandle($request);

    $this->container = $kernel->getContainer();

    // Run database tasks and check for errors.
    $installer_class = $namespace . "\\Install\\Tasks";
    $errors = (new $installer_class())->runTasks();
    if (!empty($errors)) {
      $this->fail('Failed to run installer database tasks: ' . implode(', ', $errors));
    }

    // Setup the destination to the be frontpage by default.
    \Drupal::destination()->set('/');

    // Write the core.extension configuration.
    // Required for ConfigInstaller::installDefaultConfig() to work.
    $this->container->get('config.storage')->write('core.extension', [
      'module' => array_fill_keys($modules, 0),
      'theme' => [],
    ]);

    $settings = Settings::getAll();
    $settings['php_storage']['default'] = [
      'class' => '\Drupal\Component\PhpStorage\FileStorage',
    ];
    new Settings($settings);

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out emails and collect them in state instead.
    // While this should be enforced via settings.php prior to installation,
    // some tests expect to be able to test mail system implementations.
    $GLOBALS['config']['system.mail']['interface']['default'] = 'test_mail_collector';
    $GLOBALS['config']['system.mail']['mailer_dsn'] = [
      'scheme' => 'null',
      'host' => 'null',
      'user' => NULL,
      'password' => NULL,
      'port' => NULL,
      'options' => [],
    ];
    // Manually configure the default file scheme so that modules that use file
    // functions don't have to install system and its configuration.
    // @see file_default_scheme()
    $GLOBALS['config']['system.file']['default_scheme'] = 'public';
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
    if (empty($db_url)) {
      throw new \Exception('There is no database connection so no tests can be run. You must provide a SIMPLETEST_DB environment variable to run PHPUnit based functional tests outside of run-tests.sh. See https://www.drupal.org/node/2116263#skipped-tests for more information.');
    }
    else {
      $database = Database::convertDbUrlToConnectionInfo($db_url, $this->root, TRUE);
      Database::addConnectionInfo('default', 'default', $database);
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('default');
    if (!empty($connection_info)) {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        // Replace the full table prefix definition to ensure that no table
        // prefixes of the test runner leak into the test.
        $connection_info[$target]['prefix'] = $this->databasePrefix;
      }
    }
    return $connection_info;
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
      // @todo Use extension_loaded('apcu') for non-testbot
      //   https://www.drupal.org/node/2447753.
      if (function_exists('apcu_fetch')) {
        $configuration['default']['cache_backend_class'] = ApcuFileCacheBackend::class;
      }
    }
    FileCacheFactory::setConfiguration($configuration);
    FileCacheFactory::setPrefix(Settings::getApcuPrefix('file_cache', $this->root));
  }

  /**
   * Returns Extension objects for $modules to install.
   *
   * @param string[] $modules
   *   The list of modules to install.
   *
   * @return \Drupal\Core\Extension\Extension[]
   *   Extension objects for $modules, keyed by module name.
   *
   * @throws \PHPUnit\Framework\Exception
   *   If a module is not available.
   *
   * @see \Drupal\KernelTests\KernelTestBase::enableModules()
   * @see \Drupal\Core\Extension\ModuleHandler::add()
   */
  private function getExtensionsForModules(array $modules): array {
    $extensions = [];
    $discovery = new ExtensionDiscovery($this->root);
    $discovery->setProfileDirectories([]);
    $list = $discovery->scan('module');
    foreach ($modules as $name) {
      if (!isset($list[$name])) {
        throw new Exception("Unavailable module: '$name'. If this module needs to be downloaded for testing, include it in the 'require-dev' section of your composer.json file.");
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
   * @see \Drupal\KernelTests\KernelTestBase::bootKernel()
   */
  public function register(ContainerBuilder $container) {
    // Keep the container object around for tests.
    $this->container = $container;

    $container
      ->register('datetime.time', 'Drupal\Component\Datetime\Time');
    $container
      ->register('flood', 'Drupal\Core\Flood\MemoryBackend')
      ->addArgument(new Reference('request_stack'));
    $container
      ->register('lock', 'Drupal\Core\Lock\NullLockBackend');
    $container
      ->register('cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory')
      ->addArgument(new Reference('datetime.time'));

    // Disable the super user access policy so that we are sure our tests check
    // for the right permissions.
    if (!isset($this->usesSuperUserAccessPolicy)) {
      $test_file_name = (new \ReflectionClass($this))->getFileName();
      // @todo Decide in https://www.drupal.org/project/drupal/issues/3437926
      //   how to remove this fallback behavior.
      $this->usesSuperUserAccessPolicy = !str_starts_with($test_file_name, $this->root . DIRECTORY_SEPARATOR . 'core');
    }
    $container->setParameter('security.enable_super_user', $this->usesSuperUserAccessPolicy);

    // Use memory for key value storages to avoid database queries. Store the
    // key value factory on the test object so that key value storages persist
    // container rebuilds, otherwise all state data would vanish.
    if (!isset($this->keyValue)) {
      $this->keyValue = new KeyValueMemoryFactory();
    }
    $container->set('keyvalue', $this->keyValue);
    $container->getDefinition('keyvalue')->setSynthetic(TRUE);

    // Set the default language on the minimal container.
    $container->setParameter('language.default_values', Language::$defaultValues);

    // Determine whether the test is a core test.
    $test_file_name = (new \ReflectionClass($this))->getFileName();
    // @todo Decide in https://www.drupal.org/project/drupal/issues/3395099 when/how to trigger deprecation errors or even failures for contrib modules.
    $is_core_test = str_starts_with($test_file_name, $this->root . DIRECTORY_SEPARATOR . 'core');

    if ($this->strictConfigSchema) {
      $container
        ->register('testing.config_schema_checker', ConfigSchemaChecker::class)
        ->addArgument(new Reference('config.typed'))
        ->addArgument($this->getConfigSchemaExclusions())
        ->addArgument($is_core_test)
        ->addTag('event_subscriber');
    }

    // Add event subscriber to check that an entity schema is installed before
    // any field storages are created on the entity.
    $container
      ->register('testing.field_storage_create_check', FieldStorageCreateCheckSubscriber::class)
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('entity_type.manager'))
      ->addArgument($is_core_test)
      ->addTag('event_subscriber');

    // Relax the password hashing cost in tests to avoid performance issues.
    if ($container->hasDefinition('password')) {
      $container->getDefinition('password')
        ->setArguments([PASSWORD_BCRYPT, ['cost' => 4]]);
    }

    // Add the on demand rebuild route provider service.
    $route_provider_service_name = 'router.route_provider';
    // While $container->get() does a recursive resolve, getDefinition() does
    // not, so do it ourselves.
    $id = $route_provider_service_name;
    while ($container->hasAlias($id)) {
      $id = (string) $container->getAlias($id);
    }
    $definition = $container->getDefinition($id);
    $definition->clearTag('needs_destruction');
    $container->setDefinition("test.$route_provider_service_name", $definition);

    $route_provider_definition = new Definition(RouteProvider::class);
    $route_provider_definition->setPublic(TRUE);
    $container->setDefinition($id, $route_provider_definition);

    // Remove the stored configuration importer so if used again it will be
    // built with up-to-date services.
    $this->configImporter = NULL;
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
        $exceptions = array_merge($exceptions, $class::$configSchemaCheckerExclusions);
      }
      $class = get_parent_class($class);
    }
    // Filter out any duplicates.
    return array_unique($exceptions);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertPostConditions(): void {
    // Execute registered Drupal shutdown functions prior to tearing down.
    // @see _drupal_shutdown_function()
    $callbacks = &drupal_register_shutdown_function();
    while ($callback = array_shift($callbacks)) {
      call_user_func_array($callback['callback'], $callback['arguments']);
    }

    // Shut down the kernel (if bootKernel() was called).
    // @see \Drupal\KernelTests\Core\DrupalKernel\DrupalKernelTest
    if ($this->container) {
      $this->container->get('kernel')->shutdown();
    }

    parent::assertPostConditions();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if ($this->container) {
      // Clean up mock session started in DrupalKernel::preHandle().
      /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
      $session = $this->container->get('request_stack')->getSession();
      $session->clear();
      $session->save();
    }

    // Destroy the testing kernel.
    if (isset($this->kernel)) {
      $this->kernel->shutdown();
    }

    // Remove all prefixed tables.
    $original_connection_info = Database::getConnectionInfo('simpletest_original_default');
    $original_prefix = $original_connection_info['default']['prefix'] ?? NULL;
    $test_connection_info = Database::getConnectionInfo('default');
    $test_prefix = $test_connection_info['default']['prefix'] ?? NULL;
    if ($original_prefix != $test_prefix) {
      $tables = Database::getConnection()->schema()->findTables('%');
      foreach ($tables as $table) {
        if (Database::getConnection()->schema()->dropTable($table)) {
          unset($tables[$table]);
        }
      }
    }

    // If the test used the regular file system, remove any files created.
    if (!str_starts_with($this->siteDirectory, 'vfs://')) {
      // Delete test site directory.
      $callback = function (string $path) {
        @chmod($path, 0700);
      };
      \Drupal::service('file_system')->deleteRecursive($this->siteDirectory, $callback);
    }

    // Free up memory: Own properties.
    $this->classLoader = NULL;
    $this->vfsRoot = NULL;
    $this->configImporter = NULL;

    // Clean FileCache cache.
    FileCache::reset();

    // Clean up statics, container, and settings.
    if (function_exists('drupal_static_reset')) {
      drupal_static_reset();
    }
    \Drupal::unsetContainer();
    $this->container = NULL;
    new Settings([]);

    parent::tearDown();
  }

  /**
   * Additional tear down method to close the connection at the end.
   */
  #[After]
  public function tearDownCloseDatabaseConnection() {
    // Destroy the database connection, which for example removes the memory
    // from sqlite in memory.
    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }
  }

  /**
   * Installs default configuration for a given list of modules.
   *
   * @param string|string[] $modules
   *   A module or list of modules for which to install default configuration.
   *
   * @throws \LogicException
   *   If any module in $modules is not enabled.
   */
  protected function installConfig($modules) {
    foreach ((array) $modules as $module) {
      if (!$this->container->get('module_handler')->moduleExists($module)) {
        throw new \LogicException("$module module is not installed.");
      }
      try {
        $this->container->get('config.installer')->installDefaultConfig('module', $module);
      }
      catch (\Exception $e) {
        throw new \Exception(sprintf('Exception when installing config for module %s, message was: %s', $module, $e->getMessage()), 0, $e);
      }
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
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    // Database connection schema is technically able to create database tables
    // using any valid specification, for example of a non-enabled module. But
    // ability to load the module's .install file depends on many other factors.
    // To prevent differences in test behavior and non-reproducible test
    // failures, we only allow the schema of explicitly loaded/enabled modules
    // to be installed.
    if (!$module_handler->moduleExists($module)) {
      throw new \LogicException("$module module is not installed.");
    }
    $specification = SchemaInspector::getTablesSpecification($module_handler, $module);
    /** @var \Drupal\Core\Database\Schema $schema */
    $schema = $this->container->get('database')->schema();
    $tables = (array) $tables;
    foreach ($tables as $table) {
      if ($module === 'system' && $table === 'sequences') {
        @trigger_error('Installing the table sequences with the method KernelTestBase::installSchema() is deprecated in drupal:10.2.0 and is removed from drupal:12.0.0. See https://www.drupal.org/node/3349345', E_USER_DEPRECATED);
      }
      if (empty($specification[$table])) {
        throw new \LogicException("$module module does not define a schema for table '$table'.");
      }
      $schema->createTable($table, $specification[$table]);
    }
  }

  /**
   * Installs the storage schema for a specific entity type.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   */
  protected function installEntitySchema($entity_type_id) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    \Drupal::service('entity_type.listener')->onEntityTypeCreate($entity_type);

    // For test runs, the most common storage backend is a SQL database. For
    // this case, ensure the tables got created.
    $storage = $entity_type_manager->getStorage($entity_type_id);
    if ($storage instanceof SqlEntityStorageInterface) {
      $tables = $storage->getTableMapping()->getTableNames();
      $db_schema = $this->container->get('database')->schema();
      foreach ($tables as $table) {
        $this->assertTrue($db_schema->tableExists($table), "The entity type table '$table' for the entity type '$entity_type_id' should exist.");
      }
    }
  }

  /**
   * Enables modules for this test.
   *
   * This method does not install modules fully. Services and hooks for the
   * module are available, but the install process is not performed.
   *
   * To install test modules outside of the testing environment, add
   * @code
   * $settings['extension_discovery_scan_tests'] = TRUE;
   * @endcode
   * to your settings.php.
   *
   * @param string[] $modules
   *   A list of modules to install. Dependencies are not resolved; i.e.,
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
    // Perform an ExtensionDiscovery scan as this function may receive a
    // profile that is not the current profile, and we don't yet have a cached
    // way to receive inactive profile information.
    // @todo Remove as part of https://www.drupal.org/node/2186491
    $listing = new ExtensionDiscovery($this->root);
    $module_list = $listing->scan('module');
    // In ModuleHandlerTest we pass in a profile as if it were a module.
    $module_list += $listing->scan('profile');

    // Set the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');

    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    $active_storage = $this->container->get('config.storage');
    $extension_config = $active_storage->read('core.extension');
    $extensions = $module_handler->getModuleList();

    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        continue;
      }
      $extensions[$module] = $module_list[$module];
      // Maintain the list of enabled modules in configuration.
      $extension_config['module'][$module] = 0;
    }
    $active_storage->write('core.extension', $extension_config);

    // Update the kernel to make their services available.
    $this->container->get('kernel')->updateModules($extensions, $extensions);
    $this->container = $this->container->get('kernel')->getContainer();

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if (!$module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not installed after installing it.");
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
        throw new \LogicException("$module module cannot be uninstalled because it is not installed.");
      }
      unset($module_filenames[$module]);
      $extension_config->clear('module.' . $module);
    }
    $extension_config->save();
    $module_handler->setModuleList($module_filenames);
    $module_handler->resetImplementations();
    // Update the kernel to remove their services.
    $this->container->get('kernel')->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      if ($module_handler->moduleExists($module)) {
        throw new \RuntimeException("$module module is not uninstalled after uninstalling it.");
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
    // \Drupal\Core\Render\BareHtmlPageRenderer::renderBarePage calls out to
    // system_page_attachments() directly.
    if (!\Drupal::moduleHandler()->moduleExists('system')) {
      throw new \Exception(__METHOD__ . ' requires system module to be installed.');
    }

    // Use the bare HTML page renderer to render our links.
    $renderer = $this->container->get('bare_html_page_renderer');
    $response = $renderer->renderBarePage($elements, '', 'maintenance_page');

    // Glean the content from the response object.
    $content = $response->getContent();
    $this->setRawContent($content);
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
    $settings = Settings::getInstance() ? Settings::getAll() : [];
    $settings[$name] = $value;
    new Settings($settings);
  }

  /**
   * Sets the install profile and rebuilds the container to update it.
   *
   * @param string $profile
   *   The install profile to set.
   */
  protected function setInstallProfile($profile) {
    $this->container->get('config.factory')
      ->getEditable('core.extension')
      ->set('profile', $profile)
      ->save();

    // The installation profile is provided by a container parameter. Saving
    // the configuration doesn't automatically trigger invalidation
    $this->container->get('kernel')->rebuildContainer();
  }

  /**
   * Dumps the current state of the virtual filesystem to STDOUT.
   */
  protected function vfsDump() {
    vfsStream::inspect(new vfsStreamPrintVisitor());
  }

  /**
   * Returns the modules to install for this test.
   *
   * @param string $class
   *   The fully-qualified class name of this test.
   *
   * @return array
   *   An array of modules to install.
   */
  private static function getModulesToEnable($class) {
    $modules = [];
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
    $modules = array_values(array_reverse($modules));
    return call_user_func_array('array_merge_recursive', $modules);
  }

  /**
   * Prevents serializing any properties.
   *
   * Kernel tests are run in a separate process. To do this PHPUnit creates a
   * script to run the test. If it fails, the test result object will contain a
   * stack trace which includes the test object. It will attempt to serialize
   * it. Returning an empty array prevents it from serializing anything it
   * should not.
   *
   * @return array
   *   An empty array.
   *
   * @see vendor/phpunit/phpunit/src/Util/PHP/Template/TestCaseMethod.tpl.dist
   */
  public function __sleep(): array {
    return [];
  }

}
