<?php

/**
 * @file
 * Contains \Drupal\Tests\KernelTestBase.
 */

namespace Drupal\Tests;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\Core\Entity\Schema\EntitySchemaProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for integration tests.
 *
 * Tests extending this base class can access files and the database, but the
 * entire environment is initially empty. Drupal runs in a minimal mocked
 * environment, comparable to the one in the early installer.
 *
 * The module/hook system is functional and operates on a fixed module list.
 * Additional modules needed in a test may be loaded and added to the fixed
 * module list.
 *
 * @see \Drupal\simpletest\KernelTestBase::$modules
 * @see \Drupal\simpletest\KernelTestBase::enableModules()
 */
abstract class KernelTestBase extends \PHPUnit_Framework_TestCase implements ServiceProviderInterface {

  #use AssertContentTrait;

  /**
   * Every kernel test MUST run in a separate process.
   *
   * @see \PHPUnit_Framework_TestCase::setRunTestInSeparateProcess()
   * @var bool
   */
  #protected $runTestInSeparateProcess = TRUE;

  /**
   * Implicitly TRUE by default, but MUST be TRUE for kernel tests.
   *
   * @var bool
   */
  protected $backupGlobals = TRUE;

  protected $backupStaticAttributes = TRUE;
  protected $backupStaticAttributesBlacklist = array(
    'Drupal' => array('container'),
  );

  /**
   * Fixtures shared across tests of this class.
   *
   * @var \stdClass
   */
  private static $sharedFixtures;

  protected $siteDirectory;
  protected $databasePrefix;
  protected static $currentContainer;

  /**
   * Modules to enable.
   *
   * Test classes extending this class, and any classes in the hierarchy up to
   * this class, may specify individual lists of modules to enable by setting
   * this property. The values of all properties in all classes in the hierarchy
   * are merged.
   *
   * Unlike UnitTestBase::setUp(), any modules specified in the $modules
   * property are automatically loaded and set as the fixed module list.
   *
   * Unlike WebTestBase::setUp(), the specified modules are loaded only, but not
   * automatically installed. Modules need to be installed manually, if needed.
   *
   * @see \Drupal\simpletest\KernelTestBase::enableModules()
   * @see \Drupal\simpletest\KernelTestBase::setUp()
   *
   * @var array
   */
  public static $modules = array();

  /**
   * The configuration directories for this test run.
   *
   * @var array
   */
  protected $configDirectories = array();

  /**
   * A list of stream wrappers that have been registered for this test.
   *
   * @see \Drupal\simpletest\KernelTestBase::registerStreamWrapper()
   *
   * @var array
   */
  private $streamWrappers = array();

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass() {
    parent::setUpBeforeClass();
    chdir(__DIR__ . '/../../../../');

    require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

    // Create shared fixtures for this class.
    // Booting an in-memory kernel is expensive as it involves many filesystem
    // scans. Since the initial container is identical for all tests, it is only
    // booted once for a test class and cloned for subsequent tests.
    // Tests are able to perform test-specific customizations via setUp().
    self::$sharedFixtures = new \stdClass;

    // @todo Better way? PHPUnit seems to access it from constants.
    self::$sharedFixtures->classLoader = $GLOBALS['loader'];

    // Assign a unique test site directory; account for concurrent threads.
    // @todo Remove, or replace with vfsStream. 
    do {
      $suffix = mt_rand(100000, 999999);
      self::$sharedFixtures->siteDirectory = 'sites/simpletest/' . $suffix;
    } while (is_dir(DRUPAL_ROOT . '/' . self::$sharedFixtures->siteDirectory));
    #mkdir(self::$sharedFixtures->siteDirectory, 0775, TRUE);

    self::$sharedFixtures->settings = array(
      'hash_salt' => get_called_class(),
    );

    self::$sharedFixtures->databases['default']['default'] = array(
      'driver' => 'sqlite',
      'namespace' => 'Drupal\\Core\\Database\\Driver\\sqlite',
      'host' => '',
      'database' => ':memory:',
      'username' => '',
      'password' => '',
      'prefix' => array(
        'default' => '',
      ),
    );

    // Allow for global test environment overrides.
    if (file_exists($test_env = DRUPAL_ROOT . '/sites/default/testing.services.yml')) {
      $GLOBALS['conf']['container_yamls']['testing'] = $test_env;
    }
    // Add this test class as a service provider.
    $GLOBALS['conf']['container_service_providers']['test'] = get_called_class();

    // Bootstrap a kernel. Don't use createFromRequest to retain Settings.
    new Settings(self::$sharedFixtures->settings);
    $kernel = new DrupalKernel('testing', self::$sharedFixtures->classLoader, FALSE);
    $kernel->setSitePath(self::$sharedFixtures->siteDirectory);
    $kernel->boot();
    $container = $kernel->getContainer();

    // Add a master request to the stack.
    $request = Request::create('/');
    $container->get('request_stack')->push($request);

    self::$sharedFixtures->container = $container;
  }

  public static function tearDownAfterClass() {
    self::$sharedFixtures = NULL;
    static::$currentContainer = NULL;
    \Drupal::setContainer(NULL);
    new Settings(array());
  }

  /**
   * Create and set new configuration directories.
   *
   * @see config_get_config_directory()
   *
   * @throws \RuntimeException
   *   Thrown when CONFIG_ACTIVE_DIRECTORY or CONFIG_STAGING_DIRECTORY cannot
   *   be created or made writable.
   */
  protected function prepareConfigDirectories() {
    $this->configDirectories = array();
    include_once DRUPAL_ROOT . '/core/includes/install.inc';
    foreach (array(CONFIG_ACTIVE_DIRECTORY, CONFIG_STAGING_DIRECTORY) as $type) {
      // Assign the relative path to the global variable.
      $path = $this->siteDirectory . '/config_' . $type;
      $GLOBALS['config_directories'][$type] = $path;
      // Ensure the directory can be created and is writeable.
      if (!install_ensure_config_directory($type)) {
        throw new \RuntimeException("Failed to create '$type' config directory $path");
      }
      // Provide the already resolved path for tests.
      $this->configDirectories[$type] = $path;
    }
  }

  public function __get($name) {
    if ($name === 'container') {
      return static::$currentContainer;
    }
    $denied = array(
      // @see \Drupal\simpletest\TestBase
      'testId',
      'databasePrefix', // @todo 
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
      'configImporter',
      'randomGenerator',
      // @see \Drupal\simpletest\TestBase::prepareEnvironment()
      'public_files_directory',
      'private_files_directory',
      'temp_files_directory',
      'translation_files_directory',
      'generatedTestFiles',
      // @see \Drupal\simpletest\KernelTestBase::containerBuild()
      'keyValueFactory',
    );
    if (in_array($name, $denied) || strpos($name, 'original') === 0) {
      throw new \RuntimeException(sprintf('TestBase::$%s property no longer exists', $name));
    }
  }

  public function __set($name, $value) {
    if ($name === 'container') {
      throw new \LogicException(__CLASS__ . '::$container cannot be set.');
    }
    $this->__get($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::setContainer(NULL);

    $this->siteDirectory = self::$sharedFixtures->siteDirectory;
    $this->databasePrefix = ''; // sqlite://:memory:

    // Create and set new configuration directories.
    #$this->prepareConfigDirectories();

    // Reset settings.
    new Settings(self::$sharedFixtures->settings);

    // Reset database connections/info.
    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }
    Database::setMultipleConnectionInfo(self::$sharedFixtures->databases);

    // Reset the container.
    static::$currentContainer = clone self::$sharedFixtures->container;
    \Drupal::setContainer($this->container);

    // Create a minimal core.extension configuration object so that the list of
    // enabled modules can be maintained allowing
    // \Drupal\Core\Config\ConfigInstaller::installDefaultConfig() to work.
    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    \Drupal::service('config.storage')->write('core.extension', array('module' => array(), 'theme' => array()));

    // Collect and set a fixed module list.
    $class = get_class($this);
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
    $modules = call_user_func_array('array_merge_recursive', $modules);
    if ($modules) {
      $this->enableModules($modules);
    }
    // In order to use theme functions default theme config needs to exist.
    \Drupal::config('system.theme')->set('default', 'stark');

    // Tests based on this class are entitled to use Drupal's File and
    // StreamWrapper APIs.
    // @todo Move StreamWrapper management into DrupalKernel.
    // @see https://drupal.org/node/2028109
    $this->streamWrappers = array();
    // The public stream wrapper only depends on the file_public_path setting,
    // which is provided by UnitTestBase::setUp().
    $this->registerStreamWrapper('public', 'Drupal\Core\StreamWrapper\PublicStream');
    // The temporary stream wrapper is able to operate both with and without
    // configuration.
    $this->registerStreamWrapper('temporary', 'Drupal\Core\StreamWrapper\TemporaryStream');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->container->get('kernel')->shutdown();

    // Before tearing down the test environment, ensure that no stream wrapper
    // of this test leaks into the parent environment. Unlike all other global
    // state variables in Drupal, stream wrappers are a global state construct
    // of PHP core, which has to be maintained manually.
    // @todo Move StreamWrapper management into DrupalKernel.
    // @see https://drupal.org/node/2028109
    foreach ($this->streamWrappers as $scheme => $type) {
      $this->unregisterStreamWrapper($scheme, $type);
    }

    foreach (Database::getAllConnectionInfo() as $key => $targets) {
      Database::removeConnection($key);
    }

    if (is_dir($this->siteDirectory)) {
      rmdir($this->siteDirectory);
    }

    parent::tearDown();
  }

  /**
   * Sets up the base service container for this test.
   *
   * Extend this method in your test to register additional service overrides
   * that need to persist a DrupalKernel reboot. This method is called whenever
   * the kernel is rebuilt.
   *
   * @see \Drupal\simpletest\KernelTestBase::setUp()
   * @see \Drupal\simpletest\KernelTestBase::enableModules()
   * @see \Drupal\simpletest\KernelTestBase::disableModules()
   */
  public function register(ContainerBuilder $container) {
    static::$currentContainer = $container;

    // Set the default language on the minimal container.
    $container->setParameter('language.default_values', Language::$defaultValues);

    $container->register('lock', 'Drupal\Core\Lock\NullLockBackend');
    $container->register('cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory');

    $container
      ->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory');
    $container
      ->setAlias('keyvalue', 'keyvalue.memory');

    if ($container->hasDefinition('path_processor_alias')) {
      // Prevent the alias-based path processor, which requires a url_alias db
      // table, from being registered to the path processor manager. We do this
      // by removing the tags that the compiler pass looks for. This means the
      // url generator can safely be used within tests.
      $definition = $container->getDefinition('path_processor_alias');
      $definition->clearTag('path_processor_inbound')->clearTag('path_processor_outbound');
    }

    if ($container->hasDefinition('password')) {
      $container->getDefinition('password')->setArguments(array(1));
    }
  }

  /**
   * Installs default configuration for a given list of modules.
   *
   * @param array $modules
   *   A list of modules for which to install default configuration.
   *
   * @throws \RuntimeException
   *   Thrown when any module listed in $modules is not enabled.
   */
  protected function installConfig(array $modules) {
    foreach ($modules as $module) {
      if (!$this->container->get('module_handler')->moduleExists($module)) {
        throw new \RuntimeException(format_string("'@module' module is not enabled.", array(
          '@module' => $module,
        )));
      }
      \Drupal::service('config.installer')->installDefaultConfig('module', $module);
    }
    $this->pass(format_string('Installed default config: %modules.', array(
      '%modules' => implode(', ', $modules),
    )));
  }

  /**
   * Installs a specific table from a module schema definition.
   *
   * @param string $module
   *   The name of the module that defines the table's schema.
   * @param string|array $tables
   *   The name or an array of the names of the tables to install.
   *
   * @throws \RuntimeException
   *   Thrown when $module is not enabled or when the table schema cannot be
   *   found in the module specified.
   */
  protected function installSchema($module, $tables) {
    // drupal_get_schema_unprocessed() is technically able to install a schema
    // of a non-enabled module, but its ability to load the module's .install
    // file depends on many other factors. To prevent differences in test
    // behavior and non-reproducible test failures, we only allow the schema of
    // explicitly loaded/enabled modules to be installed.
    if (!$this->container->get('module_handler')->moduleExists($module)) {
      throw new \RuntimeException(format_string("'@module' module is not enabled.", array(
        '@module' => $module,
      )));
    }
    $tables = (array) $tables;
    foreach ($tables as $table) {
      $schema = drupal_get_schema_unprocessed($module, $table);
      if (empty($schema)) {
        throw new \RuntimeException(format_string("Unknown '@table' table schema in '@module' module.", array(
          '@module' => $module,
          '@table' => $table,
        )));
      }
      $this->container->get('database')->schema()->createTable($table, $schema);
    }
    // We need to refresh the schema cache, as any call to drupal_get_schema()
    // would not know of/return the schema otherwise.
    // @todo Refactor Schema API to make this obsolete.
    drupal_get_schema(NULL, TRUE);
    $this->pass(format_string('Installed %module tables: %tables.', array(
      '%tables' => '{' . implode('}, {', $tables) . '}',
      '%module' => $module,
    )));
  }



  /**
   * Installs the tables for a specific entity type.
   *
   * @param string $entity_type_id
   *   The ID of the entity type.
   *
   * @throws \RuntimeException
   *   Thrown when the entity type does not support automatic schema installation.
   */
  protected function installEntitySchema($entity_type_id) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $this->container->get('entity.manager');
    /** @var \Drupal\Core\Database\Schema $schema_handler */
    $schema_handler = $this->container->get('database')->schema();

    $storage = $entity_manager->getStorage($entity_type_id);
    if ($storage instanceof EntitySchemaProviderInterface) {
      $schema = $storage->getSchema();
      foreach ($schema as $table_name => $table_schema) {
        $schema_handler->createTable($table_name, $table_schema);
      }

      $this->pass(String::format('Installed entity type tables for the %entity_type entity type: %tables', array(
        '%entity_type' => $entity_type_id,
        '%tables' => '{' . implode('}, {', array_keys($schema)) . '}',
      )));
    }
    else {
      throw new \RuntimeException(String::format('Entity type %entity_type does not support automatic schema installation.', array(
        '%entity-type' => $entity_type_id,
      )));
    }
  }

  /**
   * Enables modules for this test.
   *
   * @param array $modules
   *   A list of modules to enable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified with dependent modules first.
   *   The new modules are only added to the active module list and loaded.
   */
  protected function enableModules(array $modules) {
    // Set the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');

    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    $active_storage = \Drupal::service('config.storage');
    $extensions = $active_storage->read('core.extension');

    foreach ($modules as $module) {
      $module_handler->addModule($module, drupal_get_path('module', $module));
      // Maintain the list of enabled modules in configuration.
      $extensions['module'][$module] = 0;
    }
    $active_storage->write('core.extension', $extensions);

    // Update the kernel to make their services available.
    $module_filenames = $module_handler->getModuleList();
    $this->container->get('kernel')->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make _theme() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    foreach ($modules as $module) {
      $this->assertTrue($module_handler->moduleExists($module), "$module was enabled.");
    }
  }

  /**
   * Disables modules for this test.
   *
   * @param array $modules
   *   A list of modules to disable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified with dependent modules first.
   *   Code of previously active modules is still loaded. The modules are only
   *   removed from the active module list.
   */
  protected function disableModules(array $modules) {
    // Unset the list of modules in the extension handler.
    $module_handler = $this->container->get('module_handler');
    $module_filenames = $module_handler->getModuleList();
    $extension_config = $this->container->get('config.factory')->get('core.extension');
    foreach ($modules as $module) {
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
    $this->pass(format_string('Disabled modules: %modules.', array(
      '%modules' => implode(', ', $modules),
    )));
  }

  /**
   * Registers a stream wrapper for this test.
   *
   * @param string $scheme
   *   The scheme to register.
   * @param string $class
   *   The fully qualified class name to register.
   * @param int $type
   *   The Drupal Stream Wrapper API type. Defaults to
   *   STREAM_WRAPPERS_LOCAL_NORMAL.
   */
  protected function registerStreamWrapper($scheme, $class, $type = STREAM_WRAPPERS_LOCAL_NORMAL) {
    if (isset($this->streamWrappers[$scheme])) {
      $this->unregisterStreamWrapper($scheme, $this->streamWrappers[$scheme]);
    }
    $this->streamWrappers[$scheme] = $type;
    if (($type & STREAM_WRAPPERS_LOCAL) == STREAM_WRAPPERS_LOCAL) {
      stream_wrapper_register($scheme, $class);
    }
    else {
      stream_wrapper_register($scheme, $class, STREAM_IS_URL);
    }
    // @todo Revamp Drupal's stream wrapper API for D8.
    // @see https://drupal.org/node/2028109
    $wrappers = &drupal_static('file_get_stream_wrappers', array());
    $wrappers[STREAM_WRAPPERS_ALL][$scheme] = array(
      'type' => $type,
      'class' => $class,
    );
    if (($type & STREAM_WRAPPERS_WRITE_VISIBLE) == STREAM_WRAPPERS_WRITE_VISIBLE) {
      $wrappers[STREAM_WRAPPERS_WRITE_VISIBLE][$scheme] = $wrappers[STREAM_WRAPPERS_ALL][$scheme];
    }
  }

  /**
   * Unregisters a stream wrapper previously registered by this test.
   *
   * KernelTestBase::tearDown() automatically cleans up all registered
   * stream wrappers, so this usually does not have to be called manually.
   *
   * @param string $scheme
   *   The scheme to unregister.
   * @param int $type
   *   The Drupal Stream Wrapper API type of the scheme to unregister.
   */
  protected function unregisterStreamWrapper($scheme, $type) {
    stream_wrapper_unregister($scheme);
    unset($this->streamWrappers[$scheme]);
    // @todo Revamp Drupal's stream wrapper API for D8.
    // @see https://drupal.org/node/2028109
    $wrappers = &drupal_static('file_get_stream_wrappers', array());
    foreach ($wrappers as $filter => $schemes) {
      if (is_int($filter) && (($filter & $type) == $filter)) {
        unset($wrappers[$filter][$scheme]);
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
  protected function render(array $elements) {
    $content = drupal_render($elements);
    $this->setRawContent($content);
    $this->verbose('<pre style="white-space: pre-wrap">' . String::checkPlain($content));
    return $content;
  }

}
