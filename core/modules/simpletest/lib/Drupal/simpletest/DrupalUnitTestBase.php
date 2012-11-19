<?php

/**
 * @file
 * Contains Drupal\simpletest\DrupalUnitTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base test case class for Drupal unit tests.
 *
 * Tests extending this base class can access files and the database, but the
 * entire environment is initially empty. Drupal runs in a minimal mocked
 * environment, comparable to the one in the installer or update.php.
 *
 * The module/hook system is functional and operates on a fixed module list.
 * Additional modules needed in a test may be loaded and added to the fixed
 * module list.
 *
 * @see DrupalUnitTestBase::$modules
 * @see DrupalUnitTestBase::enableModules()
 */
abstract class DrupalUnitTestBase extends UnitTestBase {

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
   * @see DrupalUnitTestBase::enableModules()
   * @see DrupalUnitTestBase::setUp()
   *
   * @var array
   */
  public static $modules = array();

  /**
   * Fixed module list being used by this test.
   *
   * @var array
   *   An associative array containing the required data for the $fixed_list
   *   argument of module_list().
   *
   * @see UnitTestBase::setUp()
   * @see UnitTestBase::enableModules()
   */
  private $moduleList = array();

  private $moduleFiles;
  private $themeFiles;
  private $themeData;

  /**
   * Base service container for rebooting DrupalKernel.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private $baseContainer;

  /**
   * Sets up Drupal unit test environment.
   *
   * @see DrupalUnitTestBase::$modules
   * @see DrupalUnitTestBase
   */
  protected function setUp() {
    // Copy/prime extension file lists once to avoid filesystem scans.
    if (!isset($this->moduleFiles)) {
      $this->moduleFiles = state()->get('system.module.files') ?: array();
      $this->themeFiles = state()->get('system.theme.files') ?: array();
      $this->themeData = state()->get('system.theme.data') ?: array();
    }

    parent::setUp();

    // Build a minimal, partially mocked environment for unit tests.
    $this->setUpContainer();

    state()->set('system.module.files', $this->moduleFiles);
    state()->set('system.theme.files', $this->themeFiles);
    state()->set('system.theme.data', $this->themeData);

    // Back up the base container for enableModules().
    $this->baseContainer = clone $this->container;

    // Bootstrap the kernel.
    $this->kernel = new DrupalKernel('testing', TRUE, drupal_classloader());
    $this->kernel->boot();
    $this->container = drupal_container();

    // Ensure that the module list is initially empty.
    $this->moduleList = array();
    // Collect and set a fixed module list.
    $class = get_class($this);
    $modules = array();
    while ($class) {
      if (property_exists($class, 'modules')) {
        $modules = array_merge($modules, $class::$modules);
      }
      $class = get_parent_class($class);
    }
    $this->enableModules($modules, FALSE);
  }

  /**
   * Sets up the base service container for this test.
   *
   * Extend this method in your test to register additional service overrides
   * that need to persist a DrupalKernel reboot. This method is only called once
   * for each test.
   *
   * @see DrupalUnitTestBase::setUp()
   * @see DrupalUnitTestBase::enableModules()
   */
  protected function setUpContainer() {
    global $conf;

    $conf['lock_backend'] = 'Drupal\Core\Lock\NullLockBackend';
    $conf['cache_classes'] = array('cache' => 'Drupal\Core\Cache\MemoryBackend');
    $this->container
      ->register('config.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument($this->configDirectories[CONFIG_ACTIVE_DIRECTORY]);
    $conf['keyvalue_default'] = 'keyvalue.memory';
    $this->container
      ->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory');
  }

  /**
   * Overrides TestBase::tearDown().
   */
  protected function tearDown() {
    // Ensure that TestBase::tearDown() gets a working container.
    $this->container = $this->baseContainer;
    parent::tearDown();
  }

  /**
   * Installs a specific table from a module schema definition.
   *
   * Use this to install a particular table from System module.
   *
   * @param string $module
   *   The name of the module that defines the table's schema.
   * @param string $table
   *   The name of the table to install.
   */
  protected function installSchema($module, $table) {
    // drupal_get_schema_unprocessed() is technically able to install a schema
    // of a non-enabled module, but its ability to load the module's .install
    // file depends on many other factors. To prevent differences in test
    // behavior and non-reproducible test failures, we only allow the schema of
    // explicitly loaded/enabled modules to be installed.
    if (!module_exists($module)) {
      throw new \RuntimeException(format_string("'@module' module is not enabled.", array(
        '@module' => $module,
      )));
    }
    $schema = drupal_get_schema_unprocessed($module, $table);
    if (empty($schema)) {
      throw new \RuntimeException(format_string("Unable to retrieve '@module' module schema for '@table' table.", array(
        '@module' => $module,
        '@table' => $table,
      )));
    }
    Database::getConnection()->schema()->createTable($table, $schema);
    // We need to refresh the schema cache, as any call to drupal_get_schema()
    // would not know of/return the schema otherwise.
    // @todo Refactor Schema API to make this obsolete.
    drupal_get_schema(NULL, TRUE);
  }

  /**
   * Enables modules for this test.
   *
   * Callbacks invoked by module_enable() may need to access information
   * provided by info hooks of the new modules already. However, module_enable()
   * enables the new modules in the system.module configuration only, but that
   * has no effect, since we are operating with a fixed module list.
   *
   * @param array $modules
   *   A list of modules to enable. Dependencies are not resolved; i.e.,
   *   multiple modules have to be specified with dependent modules first.
   * @param bool $install
   *   (optional) Whether to install the list of modules via module_enable().
   *   Defaults to TRUE. If FALSE, the new modules are only added to the fixed
   *   module list and loaded.
   *
   * @todo Remove this method as soon as there is an Extensions service
   *   implementation that is able to manage a fixed module list.
   */
  protected function enableModules(array $modules, $install = TRUE) {
    // Set the modules in the fixed module_list().
    $new_enabled = array();
    foreach ($modules as $module) {
      $this->moduleList[$module]['filename'] = drupal_get_filename('module', $module);
      $new_enabled[$module] = dirname($this->moduleList[$module]['filename']);
      module_list(NULL, $this->moduleList);

      // Call module_enable() to enable (install) the new module.
      if ($install) {
        // module_enable() reboots DrupalKernel, but that builds an entirely new
        // ContainerBuilder, retrieving a fresh base container from
        // drupal_container(), which means that all of the service overrides
        // from DrupalUnitTestBase::setUpContainer() are lost, in turn triggering
        // invalid service reference errors; e.g., in TestBase::tearDown().
        // Since DrupalKernel also replaces the container in drupal_container()
        // after (re-)booting, we have to re-inject a new copy of our initial
        // base container that was built in setUpContainer().
        drupal_container(clone $this->baseContainer);
        module_enable(array($module), FALSE);
      }
    }
    // Otherwise, only ensure that the new modules are loaded.
    if (!$install) {
      module_load_all(FALSE, TRUE);
      module_implements_reset();
    }
    $kernel = $this->container->get('kernel');
    $kernel->updateModules($this->moduleList, $new_enabled, clone $this->baseContainer);
  }

}
