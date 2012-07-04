<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Module\ModuleTestBase.
 */

namespace Drupal\system\Tests\Module;

use Drupal\Core\Database\Database;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\simpletest\WebTestBase;

/**
 * Helper class for module test cases.
 */
class ModuleTestBase extends WebTestBase {
  protected $admin_user;

  function setUp() {
    parent::setUp('system_test');

    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer modules'));
    $this->drupalLogin($this->admin_user);
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
  function assertTableCount($base_table, $count = TRUE) {
    $tables = db_find_tables(Database::getConnection()->prefixTables('{' . $base_table . '}') . '%');

    if ($count) {
      return $this->assertTrue($tables, t('Tables matching "@base_table" found.', array('@base_table' => $base_table)));
    }
    return $this->assertFalse($tables, t('Tables matching "@base_table" not found.', array('@base_table' => $base_table)));
  }

  /**
   * Assert that all tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  function assertModuleTablesExist($module) {
    $tables = array_keys(drupal_get_schema_unprocessed($module));
    $tables_exist = TRUE;
    foreach ($tables as $table) {
      if (!db_table_exists($table)) {
        $tables_exist = FALSE;
      }
    }
    return $this->assertTrue($tables_exist, t('All database tables defined by the @module module exist.', array('@module' => $module)));
  }

  /**
   * Assert that none of the tables defined in a module's hook_schema() exist.
   *
   * @param $module
   *   The name of the module.
   */
  function assertModuleTablesDoNotExist($module) {
    $tables = array_keys(drupal_get_schema_unprocessed($module));
    $tables_exist = FALSE;
    foreach ($tables as $table) {
      if (db_table_exists($table)) {
        $tables_exist = TRUE;
      }
    }
    return $this->assertFalse($tables_exist, t('None of the database tables defined by the @module module exist.', array('@module' => $module)));
  }

  /**
   * Asserts that the default configuration of a module has been installed.
   *
   * @param string $module
   *   The name of the module.
   *
   * @return bool
   *   TRUE if configuration has been installed, FALSE otherwise.
   */
  function assertModuleConfig($module) {
    $module_config_dir = drupal_get_path('module', $module) . '/config';
    if (!is_dir($module_config_dir)) {
      return;
    }
    $module_file_storage = new FileStorage(array('directory' => $module_config_dir));
    $names = $module_file_storage->listAll();

    // Verify that the config directory is not empty.
    $this->assertTrue($names);

    // Look up each default configuration object name in the active store, and
    // if it exists, remove it from the stack.
    foreach ($names as $key => $name) {
      if (config($name)->get()) {
        unset($names[$key]);
      }
    }
    // Verify that all configuration has been installed (which means that $names
    // is empty).
    return $this->assertFalse($names, format_string('All default configuration of @module module found.', array('@module' => $module)));
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
  function assertNoModuleConfig($module) {
    $names = config_get_storage_names_with_prefix($module . '.');
    return $this->assertFalse($names, format_string('No configuration found for @module module.', array('@module' => $module)));
  }

  /**
   * Assert the list of modules are enabled or disabled.
   *
   * @param $modules
   *   Module list to check.
   * @param $enabled
   *   Expected module state.
   */
  function assertModules(array $modules, $enabled) {
    drupal_static_reset('system_list');
    foreach ($modules as $module) {
      if ($enabled) {
        $message = 'Module "@module" is enabled.';
      }
      else {
        $message = 'Module "@module" is not enabled.';
      }
      $this->assertEqual(module_exists($module), $enabled, t($message, array('@module' => $module)));
    }
  }

  /**
   * Verify a log entry was entered for a module's status change.
   * Called in the same way of the expected original watchdog() execution.
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
  function assertLogMessage($type, $message, $variables = array(), $severity = WATCHDOG_NOTICE, $link = '') {
    $count = db_select('watchdog', 'w')
      ->condition('type', $type)
      ->condition('message', $message)
      ->condition('variables', serialize($variables))
      ->condition('severity', $severity)
      ->condition('link', $link)
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertTrue($count > 0, t('watchdog table contains @count rows for @message', array('@count' => $count, '@message' => $message)));
  }
}
