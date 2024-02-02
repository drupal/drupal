<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Tests\BrowserTestBase;
use Drupal\TestTools\Extension\SchemaInspector;

/**
 * Helper class for module test cases.
 */
abstract class ModuleTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system_test'];

  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Assert that all tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  public function assertModuleTablesExist($module) {
    $tables = array_keys(SchemaInspector::getTablesSpecification(\Drupal::moduleHandler(), $module));
    $tables_exist = TRUE;
    $schema = Database::getConnection()->schema();
    foreach ($tables as $table) {
      if (!$schema->tableExists($table)) {
        $tables_exist = FALSE;
      }
    }
    $this->assertTrue($tables_exist, "All database tables defined by the $module module exist.");
  }

  /**
   * Assert that none of the tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  public function assertModuleTablesDoNotExist($module) {
    $tables = array_keys(SchemaInspector::getTablesSpecification(\Drupal::moduleHandler(), $module));
    $tables_exist = FALSE;
    $schema = Database::getConnection()->schema();
    foreach ($tables as $table) {
      if ($schema->tableExists($table)) {
        $tables_exist = TRUE;
      }
    }
    $this->assertFalse($tables_exist, "None of the database tables defined by the $module module exist.");
  }

  /**
   * Asserts that the default configuration of a module has been installed.
   *
   * @param string $module
   *   The name of the module.
   */
  public function assertModuleConfig($module) {
    $module_config_dir = $this->getModulePath($module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
    if (!is_dir($module_config_dir)) {
      return;
    }
    $module_file_storage = new FileStorage($module_config_dir);

    // Verify that the module's default config directory is not empty and
    // contains default configuration files (instead of something else).
    $all_names = $module_file_storage->listAll();
    if (empty($all_names)) {
      // Module has an empty config directory. For example it might contain a
      // schema directory.
      return;
    }
    $this->assertNotEmpty($all_names);

    $module_config_dependencies = \Drupal::service('config.manager')->findConfigEntityDependencies('module', [$module]);
    // Look up each default configuration object name in the active
    // configuration, and if it exists, remove it from the stack.
    $names = $module_file_storage->listAll();
    foreach ($names as $key => $name) {
      if ($this->config($name)->get()) {
        unset($names[$key]);
      }
      // All configuration in a module's config/install directory should depend
      // on the module as it must be removed on uninstall or the module will not
      // be re-installable.
      $this->assertTrue(str_starts_with($name, $module . '.') || isset($module_config_dependencies[$name]), "Configuration $name provided by $module in its config/install directory does not depend on it.");
    }
    // Verify that all configuration has been installed (which means that $names
    // is empty).
    $this->assertEmpty($names, "All default configuration of $module module found.");
  }

  /**
   * Asserts that no configuration exists for a given module.
   *
   * @param string $module
   *   The name of the module.
   */
  public function assertNoModuleConfig($module) {
    $names = \Drupal::configFactory()->listAll($module . '.');
    $this->assertEmpty($names, "No configuration found for $module module.");
  }

  /**
   * Assert the list of modules are enabled or disabled.
   *
   * @param $modules
   *   Module list to check.
   * @param $enabled
   *   Expected module state.
   */
  public function assertModules(array $modules, $enabled) {
    $this->rebuildContainer();
    foreach ($modules as $module) {
      if ($enabled) {
        $message = 'Module "%s" is enabled.';
      }
      else {
        $message = 'Module "%s" is not enabled.';
      }
      $this->assertEquals($enabled, $this->container->get('module_handler')->moduleExists($module), sprintf($message, $module));
    }
  }

  /**
   * Verify a log entry was entered for a module's status change.
   *
   * @param $type
   *   The category to which this message belongs.
   * @param $message
   *   The message to store in the log. Keep $message translatable
   *   by not concatenating dynamic values into it! Variables in the
   *   message should be added by using placeholder strings alongside
   *   the variables argument to declare the value of the placeholders.
   *   See t() for documentation on how $message and $variables interact.
   * @param $variables
   *   Array of variables to replace in the message on display or
   *   NULL if message is already translated or not possible to
   *   translate.
   * @param $severity
   *   The severity of the message, as per RFC 3164.
   * @param $link
   *   A link to associate with the message.
   */
  public function assertLogMessage($type, $message, $variables = [], $severity = RfcLogLevel::NOTICE, $link = '') {
    $this->assertNotEmpty(Database::getConnection()->select('watchdog', 'w')
      ->condition('type', $type)
      ->condition('message', $message)
      ->condition('variables', serialize($variables))
      ->condition('severity', $severity)
      ->condition('link', $link)
      ->countQuery()
      ->execute()
      ->fetchField()
    );
  }

}
