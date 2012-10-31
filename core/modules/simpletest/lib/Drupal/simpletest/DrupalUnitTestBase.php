<?php

/**
 * @file
 * Contains Drupal\simpletest\DrupalUnitTestBase.
 */

namespace Drupal\simpletest;

use Drupal\Core\Database\Database;

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
   * Sets up Drupal unit test environment.
   *
   * @see DrupalUnitTestBase::$modules
   * @see DrupalUnitTestBase
   */
  protected function setUp() {
    global $conf;

    // Copy/prime extension file lists once to avoid filesystem scans.
    if (!isset($this->moduleFiles)) {
      $this->moduleFiles = state()->get('system.module.files') ?: array();
      $this->themeFiles = state()->get('system.theme.files') ?: array();
      $this->themeData = state()->get('system.theme.data') ?: array();
    }

    parent::setUp();

    // Provide a minimal, partially mocked environment for unit tests.
    $conf['lock_backend'] = 'Drupal\Core\Lock\NullLockBackend';
    $conf['cache_classes'] = array('cache' => 'Drupal\Core\Cache\MemoryBackend');
    $this->container
      ->register('config.storage', 'Drupal\Core\Config\FileStorage')
      ->addArgument($this->configDirectories[CONFIG_ACTIVE_DIRECTORY]);
    $conf['keyvalue_default'] = 'keyvalue.memory';
    $this->container
      ->register('keyvalue.memory', 'Drupal\Core\KeyValueStore\KeyValueMemoryFactory');

    state()->set('system.module.files', $this->moduleFiles);
    state()->set('system.theme.files', $this->themeFiles);
    state()->set('system.theme.data', $this->themeData);

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
   * Installs a specific table from a module schema definition.
   *
   * @param string $module
   *   The name of the module that defines the table's schema.
   * @param string $table
   *   The name of the table to install.
   */
  protected function installSchema($module, $table) {
    require_once DRUPAL_ROOT . '/' . drupal_get_path('module', $module) . "/$module.install";
    $function = $module . '_schema';
    $schema = $function();
    Database::getConnection()->schema()->createTable($table, $schema[$table]);
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
   *   A list of modules to enable.
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
    foreach ($modules as $module) {
      $this->moduleList[$module]['filename'] = drupal_get_filename('module', $module);
    }
    module_list(NULL, $this->moduleList);

    // Call module_enable() to enable (install) the new modules.
    if ($install) {
      module_enable($modules);
    }
    // Otherwise, only ensure that the new modules are loaded.
    else {
      module_load_all(FALSE, TRUE);
    }
  }
}
