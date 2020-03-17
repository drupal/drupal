<?php

namespace Drupal\simpletest;

@trigger_error(__NAMESPACE__ . '\KernelTestBase is deprecated in Drupal 8.0.x, will be removed before Drupal 9.0.0. Use \Drupal\KernelTests\KernelTestBase instead.', E_USER_DEPRECATED);

use Drupal\Component\Utility\Html;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Config\Development\ConfigSchemaChecker;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\TestServiceProvider;
use Symfony\Component\DependencyInjection\Parameter;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for functional integration tests.
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
 * @see \Drupal\Tests\KernelTestBase::$modules
 * @see \Drupal\Tests\KernelTestBase::enableModules()
 * @see \Drupal\Tests\KernelTestBase::installConfig()
 * @see \Drupal\Tests\KernelTestBase::installEntitySchema()
 * @see \Drupal\Tests\KernelTestBase::installSchema()
 * @see \Drupal\Tests\BrowserTestBase
 *
 * @deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\KernelTests\KernelTestBase instead.
 *
 * @ingroup testing
 */
abstract class KernelTestBase extends TestBase {

  use AssertContentTrait;

  /**
   * Modules to enable.
   *
   * Test classes extending this class, and any classes in the hierarchy up to
   * this class, may specify individual lists of modules to enable by setting
   * this property. The values of all properties in all classes in the hierarchy
   * are merged.
   *
   * Any modules specified in the $modules property are automatically loaded and
   * set as the fixed module list.
   *
   * Unlike WebTestBase::setUp(), the specified modules are loaded only, but not
   * automatically installed. Modules need to be installed manually, if needed.
   *
   * @see \Drupal\simpletest\KernelTestBase::enableModules()
   * @see \Drupal\simpletest\KernelTestBase::setUp()
   *
   * @var array
   */
  public static $modules = [];

  private $moduleFiles;
  private $themeFiles;

  /**
   * The configuration directories for this test run.
   *
   * @var array
   */
  protected $configDirectories = [];

  /**
   * A KeyValueMemoryFactory instance to use when building the container.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueMemoryFactory
   */
  protected $keyValueFactory;

  /**
   * Array of registered stream wrappers.
   *
   * @var array
   */
  protected $streamWrappers = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($test_id = NULL) {
    parent::__construct($test_id);
    $this->skipClasses[__CLASS__] = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function beforePrepareEnvironment() {
    // Copy/prime extension file lists once to avoid filesystem scans.
    if (!isset($this->moduleFiles)) {
      $this->moduleFiles = \Drupal::state()->get('system.module.files') ?: [];
      $this->themeFiles = \Drupal::state()->get('system.theme.files') ?: [];
    }
  }

  /**
   * Create and set new configuration directories.
   *
   * @see \Drupal\Core\Site\Settings::getConfigDirectory()
   *
   * @throws \RuntimeException
   *   Thrown when the configuration sync directory cannot be created or made
   *   writable.
   *
   * @return string
   *   The config sync directory path.
   */
  protected function prepareConfigDirectories() {
    $this->configDirectories = [];
    // Assign the relative path to the global variable.
    $path = $this->siteDirectory . '/config_' . CONFIG_SYNC_DIRECTORY;
    // Ensure the directory can be created and is writeable.
    if (!\Drupal::service('file_system')->prepareDirectory($path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException("Failed to create '" . CONFIG_SYNC_DIRECTORY . "' config directory $path");
    }
    // Provide the already resolved path for tests.
    $this->configDirectories[CONFIG_SYNC_DIRECTORY] = $path;
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->keyValueFactory = new KeyValueMemoryFactory();

    // Back up settings from TestBase::prepareEnvironment().
    $settings = Settings::getAll();

    // Allow for test-specific overrides.
    $directory = DRUPAL_ROOT . '/' . $this->siteDirectory;
    $settings_services_file = DRUPAL_ROOT . '/' . $this->originalSite . '/testing.services.yml';
    $container_yamls = [];
    if (file_exists($settings_services_file)) {
      // Copy the testing-specific service overrides in place.
      $testing_services_file = $directory . '/services.yml';
      copy($settings_services_file, $testing_services_file);
      $container_yamls[] = $testing_services_file;
    }
    $settings_testing_file = DRUPAL_ROOT . '/' . $this->originalSite . '/settings.testing.php';
    if (file_exists($settings_testing_file)) {
      // Copy the testing-specific settings.php overrides in place.
      copy($settings_testing_file, $directory . '/settings.testing.php');
    }

    if (file_exists($directory . '/settings.testing.php')) {
      // Add the name of the testing class to settings.php and include the
      // testing specific overrides
      $hash_salt = Settings::getHashSalt();
      $test_class = get_class($this);
      $container_yamls_export = Variable::export($container_yamls);
      $php = <<<EOD
<?php

\$settings['hash_salt'] = '$hash_salt';
\$settings['container_yamls'] = $container_yamls_export;

\$test_class = '$test_class';
include DRUPAL_ROOT . '/' . \$site_path . '/settings.testing.php';
EOD;
      file_put_contents($directory . '/settings.php', $php);
    }

    // Add this test class as a service provider.
    // @todo Remove the indirection; implement ServiceProviderInterface instead.
    $GLOBALS['conf']['container_service_providers']['TestServiceProvider'] = TestServiceProvider::class;

    // Bootstrap a new kernel.
    $class_loader = require DRUPAL_ROOT . '/autoload.php';
    $this->kernel = new DrupalKernel('testing', $class_loader, FALSE);
    $request = Request::create('/');
    $site_path = DrupalKernel::findSitePath($request);
    $this->kernel->setSitePath($site_path);
    if (file_exists($directory . '/settings.testing.php')) {
      Settings::initialize(DRUPAL_ROOT, $site_path, $class_loader);
    }
    // Set the module list upfront to avoid setting the kernel into the
    // pre-installer mode.
    $this->kernel->updateModules([], []);
    $this->kernel->boot();

    // Ensure database install tasks have been run.
    require_once __DIR__ . '/../../../includes/install.inc';
    $connection = Database::getConnection();
    $errors = db_installer_object($connection->driver())->runTasks();
    if (!empty($errors)) {
      $this->fail('Failed to run installer database tasks: ' . implode(', ', $errors));
    }

    // Reboot the kernel because the container might contain a connection to the
    // database that has been closed during the database install tasks. This
    // prevents any services created during the first boot from having stale
    // database connections, for example, \Drupal\Core\Config\DatabaseStorage.
    $this->kernel->shutdown();
    // Set the module list upfront to avoid setting the kernel into the
    // pre-installer mode.
    $this->kernel->updateModules([], []);
    $this->kernel->boot();

    // Save the original site directory path, so that extensions in the
    // site-specific directory can still be discovered in the test site
    // environment.
    // @see \Drupal\Core\Extension\ExtensionDiscovery::scan()
    $settings['test_parent_site'] = $this->originalSite;

    // Create and set new configuration directories.
    $settings['config_sync_directory'] = $this->prepareConfigDirectories();

    // Restore and merge settings.
    // DrupalKernel::boot() initializes new Settings, and the containerBuild()
    // method sets additional settings.
    new Settings($settings + Settings::getAll());

    // Set the request scope.
    $this->container = $this->kernel->getContainer();
    $this->container->get('request_stack')->push($request);

    // Re-inject extension file listings into state, unless the key/value
    // service was overridden (in which case its storage does not exist yet).
    if ($this->container->get('keyvalue') instanceof KeyValueMemoryFactory) {
      $this->container->get('state')->set('system.module.files', $this->moduleFiles);
      $this->container->get('state')->set('system.theme.files', $this->themeFiles);
    }

    // Create a minimal core.extension configuration object so that the list of
    // enabled modules can be maintained allowing
    // \Drupal\Core\Config\ConfigInstaller::installDefaultConfig() to work.
    // Write directly to active storage to avoid early instantiation of
    // the event dispatcher which can prevent modules from registering events.
    \Drupal::service('config.storage')->write('core.extension', ['module' => [], 'theme' => [], 'profile' => '']);

    // Collect and set a fixed module list.
    $class = get_class($this);
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
    $modules = array_reverse($modules);
    $modules = call_user_func_array('array_merge_recursive', $modules);
    if ($modules) {
      $this->enableModules($modules);
    }

    // Tests based on this class are entitled to use Drupal's File and
    // StreamWrapper APIs.
    \Drupal::service('file_system')->prepareDirectory($this->publicFilesDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->settingsSet('file_public_path', $this->publicFilesDirectory);
    $this->streamWrappers = [];
    $this->registerStreamWrapper('public', 'Drupal\Core\StreamWrapper\PublicStream');
    // The temporary stream wrapper is able to operate both with and without
    // configuration.
    $this->registerStreamWrapper('temporary', 'Drupal\Core\StreamWrapper\TemporaryStream');

    // Manually configure the test mail collector implementation to prevent
    // tests from sending out emails and collect them in state instead.
    // While this should be enforced via settings.php prior to installation,
    // some tests expect to be able to test mail system implementations.
    $GLOBALS['config']['system.mail']['interface']['default'] = 'test_mail_collector';
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    if ($this->kernel instanceof DrupalKernel) {
      $this->kernel->shutdown();
    }
    // Before tearing down the test environment, ensure that no stream wrapper
    // of this test leaks into the parent environment. Unlike all other global
    // state variables in Drupal, stream wrappers are a global state construct
    // of PHP core, which has to be maintained manually.
    // @todo Move StreamWrapper management into DrupalKernel.
    // @see https://www.drupal.org/node/2028109
    foreach ($this->streamWrappers as $scheme => $type) {
      $this->unregisterStreamWrapper($scheme, $type);
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
  public function containerBuild(ContainerBuilder $container) {
    // Keep the container object around for tests.
    $this->container = $container;

    // Set the default language on the minimal container.
    $this->container->setParameter('language.default_values', $this->defaultLanguageData());

    $container->register('lock', 'Drupal\Core\Lock\NullLockBackend');
    $container->register('cache_factory', 'Drupal\Core\Cache\MemoryBackendFactory');

    $container
      ->register('config.storage', 'Drupal\Core\Config\DatabaseStorage')
      ->addArgument(Database::getConnection())
      ->addArgument('config');

    if ($this->strictConfigSchema) {
      $container
        ->register('testing.config_schema_checker', ConfigSchemaChecker::class)
        ->addArgument(new Reference('config.typed'))
        ->addArgument($this->getConfigSchemaExclusions())
        ->addTag('event_subscriber');
    }

    $keyvalue_options = $container->getParameter('factory.keyvalue') ?: [];
    $keyvalue_options['default'] = 'keyvalue.memory';
    $container->setParameter('factory.keyvalue', $keyvalue_options);
    $container->set('keyvalue.memory', $this->keyValueFactory);
    if (!$container->has('keyvalue')) {
      // TestBase::setUp puts a completely empty container in
      // $this->container which is somewhat the mirror of the empty
      // environment being set up. Unit tests need not to waste time with
      // getting a container set up for them. Drupal Unit Tests might just get
      // away with a simple container holding the absolute bare minimum. When
      // a kernel is overridden then there's no need to re-register the keyvalue
      // service but when a test is happy with the superminimal container put
      // together here, it still might a keyvalue storage for anything using
      // \Drupal::state() -- that's why a memory service was added in the first
      // place.
      $container->register('settings', 'Drupal\Core\Site\Settings')
        ->setFactoryClass('Drupal\Core\Site\Settings')
        ->setFactoryMethod('getInstance');

      $container
        ->register('keyvalue', 'Drupal\Core\KeyValueStore\KeyValueFactory')
        ->addArgument(new Reference('service_container'))
        ->addArgument(new Parameter('factory.keyvalue'));

      $container->register('state', 'Drupal\Core\State\State')
        ->addArgument(new Reference('keyvalue'));
    }

    if ($container->hasDefinition('path_alias.path_processor')) {
      // The alias-based processor requires the path_alias entity schema to be
      // installed, so we prevent it from being registered to the path processor
      // manager. We do this by removing the tags that the compiler pass looks
      // for. This means that the URL generator can safely be used within tests.
      $definition = $container->getDefinition('path_alias.path_processor');
      $definition->clearTag('path_processor_inbound')->clearTag('path_processor_outbound');
    }

    if ($container->hasDefinition('password')) {
      $container->getDefinition('password')->setArguments([1]);
    }

    // Register the stream wrapper manager.
    $container
      ->register('stream_wrapper_manager', 'Drupal\Core\StreamWrapper\StreamWrapperManager')
      ->addArgument(new Reference('module_handler'))
      ->addMethodCall('setContainer', [new Reference('service_container')]);

    $request = Request::create('/');
    $container->get('request_stack')->push($request);
  }

  /**
   * Provides the data for setting the default language on the container.
   *
   * @return array
   *   The data array for the default language.
   */
  protected function defaultLanguageData() {
    return Language::$defaultValues;
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
        throw new \RuntimeException("'$module' module is not enabled");
      }
      \Drupal::service('config.installer')->installDefaultConfig('module', $module);
    }
    $this->pass(new FormattableMarkup('Installed default config: %modules.', [
      '%modules' => implode(', ', $modules),
    ]));
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
    // drupal_get_module_schema() is technically able to install a schema
    // of a non-enabled module, but its ability to load the module's .install
    // file depends on many other factors. To prevent differences in test
    // behavior and non-reproducible test failures, we only allow the schema of
    // explicitly loaded/enabled modules to be installed.
    if (!$this->container->get('module_handler')->moduleExists($module)) {
      throw new \RuntimeException("'$module' module is not enabled");
    }

    $tables = (array) $tables;
    foreach ($tables as $table) {
      $schema = drupal_get_module_schema($module, $table);
      if (empty($schema)) {
        // BC layer to avoid some contrib tests to fail.
        // @todo Remove the BC layer before 8.1.x release.
        // @see https://www.drupal.org/node/2670360
        // @see https://www.drupal.org/node/2670454
        if ($module == 'system') {
          continue;
        }
        throw new \RuntimeException("Unknown '$table' table schema in '$module' module.");
      }
      $this->container->get('database')->schema()->createTable($table, $schema);
    }
    $this->pass(new FormattableMarkup('Installed %module tables: %tables.', [
      '%tables' => '{' . implode('}, {', $tables) . '}',
      '%module' => $module,
    ]));
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
          $this->fail(new FormattableMarkup('Installed entity type table for the %entity_type entity type: %table', [
            '%entity_type' => $entity_type_id,
            '%table' => $table,
          ]));
          $all_tables_exist = FALSE;
        }
      }
      if ($all_tables_exist) {
        $this->pass(new FormattableMarkup('Installed entity type tables for the %entity_type entity type: %tables', [
          '%entity_type' => $entity_type_id,
          '%tables' => '{' . implode('}, {', $tables) . '}',
        ]));
      }
    }
  }

  /**
   * Enables modules for this test.
   *
   * To install test modules outside of the testing environment, add
   * @code
   * $settings['extension_discovery_scan_tests'] = TRUE;
   * @endcode
   * to your settings.php.
   *
   * @param array $modules
   *   A list of modules to enable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified with dependent modules first.
   *   The new modules are only added to the active module list and loaded.
   */
  protected function enableModules(array $modules) {
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
    $active_storage = \Drupal::service('config.storage');
    $extensions = $active_storage->read('core.extension');

    foreach ($modules as $module) {
      $module_handler->addModule($module, $module_list[$module]->getPath());
      // Maintain the list of enabled modules in configuration.
      $extensions['module'][$module] = 0;
    }
    $active_storage->write('core.extension', $extensions);

    // Update the kernel to make their services available.
    $module_filenames = $module_handler->getModuleList();
    $this->kernel->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $this->container->get('module_handler')->reload();
    $this->pass(new FormattableMarkup('Enabled modules: %modules.', [
      '%modules' => implode(', ', $modules),
    ]));
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
    $extension_config = $this->config('core.extension');
    foreach ($modules as $module) {
      unset($module_filenames[$module]);
      $extension_config->clear('module.' . $module);
    }
    $extension_config->save();
    $module_handler->setModuleList($module_filenames);
    $module_handler->resetImplementations();
    // Update the kernel to remove their services.
    $this->kernel->updateModules($module_filenames, $module_filenames);

    // Ensure isLoaded() is TRUE in order to make
    // \Drupal\Core\Theme\ThemeManagerInterface::render() work.
    // Note that the kernel has rebuilt the container; this $module_handler is
    // no longer the $module_handler instance from above.
    $module_handler = $this->container->get('module_handler');
    $module_handler->reload();
    $this->pass(new FormattableMarkup('Disabled modules: %modules.', [
      '%modules' => implode(', ', $modules),
    ]));
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
   *   StreamWrapperInterface::NORMAL.
   */
  protected function registerStreamWrapper($scheme, $class, $type = StreamWrapperInterface::NORMAL) {
    $this->container->get('stream_wrapper_manager')->registerWrapper($scheme, $class, $type);
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
    $response = $renderer->renderBarePage($elements, '', 'maintenance_page');

    // Glean the content from the response object.
    $content = $response->getContent();
    $this->setRawContent($content);
    $this->verbose('<pre style="white-space: pre-wrap">' . Html::escape($content));
    return $content;
  }

}
