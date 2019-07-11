<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Tests\BrowserTestBase;

/**
 * Helper class for module test cases.
 */
abstract class ModuleTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system_test'];

  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['access administration pages', 'administer modules']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Assert there are tables that begin with the specified base table name.
   *
   * @param $base_table
   *   Beginning of table name to look for.
   * @param $count
   *   (optional) Whether or not to assert that there are tables that match the
   *   specified base table. Defaults to TRUE.
   */
  public function assertTableCount($base_table, $count = TRUE) {
    $connection = Database::getConnection();
    $tables = $connection->schema()->findTables($connection->prefixTables('{' . $base_table . '}') . '%');

    if ($count) {
      return $this->assertTrue($tables, new FormattableMarkup('Tables matching "@base_table" found.', ['@base_table' => $base_table]));
    }
    return $this->assertFalse($tables, new FormattableMarkup('Tables matching "@base_table" not found.', ['@base_table' => $base_table]));
  }

  /**
   * Assert that all tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  public function assertModuleTablesExist($module) {
    $tables = array_keys(drupal_get_module_schema($module));
    $tables_exist = TRUE;
    $schema = Database::getConnection()->schema();
    foreach ($tables as $table) {
      if (!$schema->tableExists($table)) {
        $tables_exist = FALSE;
      }
    }
    return $this->assertTrue($tables_exist, new FormattableMarkup('All database tables defined by the @module module exist.', ['@module' => $module]));
  }

  /**
   * Assert that none of the tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  public function assertModuleTablesDoNotExist($module) {
    $tables = array_keys(drupal_get_module_schema($module));
    $tables_exist = FALSE;
    $schema = Database::getConnection()->schema();
    foreach ($tables as $table) {
      if ($schema->tableExists($table)) {
        $tables_exist = TRUE;
      }
    }
    return $this->assertFalse($tables_exist, new FormattableMarkup('None of the database tables defined by the @module module exist.', ['@module' => $module]));
  }

  /**
   * Asserts that the default configuration of a module has been installed.
   *
   * @param string $module
   *   The name of the module.
   *
   * @return bool|null
   *   TRUE if configuration has been installed, FALSE otherwise. Returns NULL
   *   if the module configuration directory does not exist or does not contain
   *   any configuration files.
   */
  public function assertModuleConfig($module) {
    $module_config_dir = drupal_get_path('module', $module) . '/' . InstallStorage::CONFIG_INSTALL_DIRECTORY;
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
    $this->assertTrue($all_names);

    $module_config_dependencies = \Drupal::service('config.manager')->findConfigEntityDependents('module', [$module]);
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
      $this->assertTrue(strpos($name, $module . '.') === 0 || isset($module_config_dependencies[$name]), "Configuration $name provided by $module in its config/install directory does not depend on it.");
    }
    // Verify that all configuration has been installed (which means that $names
    // is empty).
    return $this->assertFalse($names, new FormattableMarkup('All default configuration of @module module found.', ['@module' => $module]));
  }

  /**
   * Asserts that no configuration exists for a given module.
   *
   * @param string $module
   *   The name of the module.
   *
   * @return bool
   *   TRUE if no configuration was found, FALSE otherwise.
   */
  public function assertNoModuleConfig($module) {
    $names = \Drupal::configFactory()->listAll($module . '.');
    return $this->assertFalse($names, new FormattableMarkup('No configuration found for @module module.', ['@module' => $module]));
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
        $message = 'Module "@module" is enabled.';
      }
      else {
        $message = 'Module "@module" is not enabled.';
      }
      $this->assertEqual($this->container->get('module_handler')->moduleExists($module), $enabled, new FormattableMarkup($message, ['@module' => $module]));
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
    $count = Database::getConnection()->select('watchdog', 'w')
      ->condition('type', $type)
      ->condition('message', $message)
      ->condition('variables', serialize($variables))
      ->condition('severity', $severity)
      ->condition('link', $link)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertTrue($count > 0, new FormattableMarkup('watchdog table contains @count rows for @message', ['@count' => $count, '@message' => new FormattableMarkup($message, $variables)]));
  }

}
