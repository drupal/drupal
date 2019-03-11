<?php

namespace Drupal\simpletest\Tests;

use Drupal\Core\Database\Database;
use Drupal\field\Entity\FieldConfig;
use Drupal\simpletest\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Tests KernelTestBase functionality.
 *
 * @group simpletest
 */
class KernelTestBaseTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $php = <<<'EOS'
<?php
# Make sure that the $test_class variable is defined when this file is included.
if ($test_class) {
}

# Define a function to be able to check that this file was loaded with
# function_exists().
if (!function_exists('simpletest_test_stub_settings_function')) {
  function simpletest_test_stub_settings_function() {}
}
EOS;

    $settings_testing_file = $this->siteDirectory . '/settings.testing.php';
    file_put_contents($settings_testing_file, $php);

    $original_container = $this->originalContainer;
    parent::setUp();
    $this->assertNotIdentical(\Drupal::getContainer(), $original_container, 'KernelTestBase test creates a new container.');
  }

  /**
   * Tests expected behavior of setUp().
   */
  public function testSetUp() {
    $modules = ['entity_test'];
    $table = 'entity_test';

    // Verify that specified $modules have been loaded.
    $this->assertTrue(function_exists('entity_test_entity_bundle_info'), 'entity_test.module was loaded.');
    // Verify that there is a fixed module list.
    $this->assertIdentical(array_keys(\Drupal::moduleHandler()->getModuleList()), $modules);
    $this->assertIdentical(\Drupal::moduleHandler()->getImplementations('entity_bundle_info'), ['entity_test']);
    $this->assertIdentical(\Drupal::moduleHandler()->getImplementations('entity_type_alter'), ['entity_test']);

    // Verify that no modules have been installed.
    $this->assertFalse(Database::getConnection()->schema()->tableExists($table), "'$table' database table not found.");

    // Verify that the settings.testing.php got taken into account.
    $this->assertTrue(function_exists('simpletest_test_stub_settings_function'));

    // Ensure that the database tasks have been run during set up. Neither MySQL
    // nor SQLite make changes that are testable.
    $database = $this->container->get('database');
    if ($database->driver() == 'pgsql') {
      $this->assertEqual('on', $database->query("SHOW standard_conforming_strings")->fetchField());
      $this->assertEqual('escape', $database->query("SHOW bytea_output")->fetchField());
    }
  }

  /**
   * Tests expected load behavior of enableModules().
   */
  public function testEnableModulesLoad() {
    $module = 'field_test';

    // Verify that the module does not exist yet.
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists($module), "$module module not found.");
    $list = array_keys(\Drupal::moduleHandler()->getModuleList());
    $this->assertFalse(in_array($module, $list), "$module module not found in the extension handler's module list.");
    $list = \Drupal::moduleHandler()->getImplementations('entity_display_build_alter');
    $this->assertFalse(in_array($module, $list), "{$module}_entity_display_build_alter() in \Drupal::moduleHandler()->getImplementations() not found.");

    // Enable the module.
    $this->enableModules([$module]);

    // Verify that the module exists.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists($module), "$module module found.");
    $list = array_keys(\Drupal::moduleHandler()->getModuleList());
    $this->assertTrue(in_array($module, $list), "$module module found in the extension handler's module list.");
    $list = \Drupal::moduleHandler()->getImplementations('query_efq_table_prefixing_test_alter');
    $this->assertTrue(in_array($module, $list), "{$module}_query_efq_table_prefixing_test_alter() in \Drupal::moduleHandler()->getImplementations() found.");
  }

  /**
   * Tests expected installation behavior of enableModules().
   */
  public function testEnableModulesInstall() {
    $module = 'module_test';
    $table = 'module_test';

    // Verify that the module does not exist yet.
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists($module), "$module module not found.");
    $list = array_keys(\Drupal::moduleHandler()->getModuleList());
    $this->assertFalse(in_array($module, $list), "$module module not found in the extension handler's module list.");
    $list = \Drupal::moduleHandler()->getImplementations('hook_info');
    $this->assertFalse(in_array($module, $list), "{$module}_hook_info() in \Drupal::moduleHandler()->getImplementations() not found.");

    $this->assertFalse(Database::getConnection()->schema()->tableExists($table), "'$table' database table not found.");

    // Install the module.
    \Drupal::service('module_installer')->install([$module]);

    // Verify that the enabled module exists.
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists($module), "$module module found.");
    $list = array_keys(\Drupal::moduleHandler()->getModuleList());
    $this->assertTrue(in_array($module, $list), "$module module found in the extension handler's module list.");
    $list = \Drupal::moduleHandler()->getImplementations('hook_info');
    $this->assertTrue(in_array($module, $list), "{$module}_hook_info() in \Drupal::moduleHandler()->getImplementations() found.");

    $this->assertTrue(Database::getConnection()->schema()->tableExists($table), "'$table' database table found.");
    $schema = drupal_get_module_schema($module, $table);
    $this->assertTrue($schema, "'$table' table schema found.");
  }

  /**
   * Tests installing modules with DependencyInjection services.
   */
  public function testEnableModulesInstallContainer() {
    // Install Node module.
    $this->enableModules(['user', 'field', 'node']);

    $this->installEntitySchema('node', ['node', 'node_field_data']);
    // Perform an entity query against node.
    $query = \Drupal::entityQuery('node');
    // Disable node access checks, since User module is not enabled.
    $query->accessCheck(FALSE);
    $query->condition('nid', 1);
    $query->execute();
    $this->pass('Entity field query was executed.');
  }

  /**
   * Tests expected behavior of installSchema().
   */
  public function testInstallSchema() {
    $module = 'entity_test';
    $table = 'entity_test_example';
    // Verify that we can install a table from the module schema.
    $this->installSchema($module, $table);
    $this->assertTrue(Database::getConnection()->schema()->tableExists($table), "'$table' database table found.");

    // Verify that the schema is known to Schema API.
    $schema = drupal_get_module_schema($module, $table);
    $this->assertTrue($schema, "'$table' table schema found.");

    // Verify that a unknown table from an enabled module throws an error.
    $table = 'unknown_entity_test_table';
    try {
      $this->installSchema($module, $table);
      $this->fail('Exception for non-retrievable schema found.');
    }
    catch (\Exception $e) {
      $this->pass('Exception for non-retrievable schema found.');
    }
    $this->assertFalse(Database::getConnection()->schema()->tableExists($table), "'$table' database table not found.");
    $schema = drupal_get_module_schema($module, $table);
    $this->assertFalse($schema, "'$table' table schema not found.");

    // Verify that a table from a unknown module cannot be installed.
    $module = 'database_test';
    $table = 'test';
    try {
      $this->installSchema($module, $table);
      $this->fail('Exception for non-retrievable schema found.');
    }
    catch (\Exception $e) {
      $this->pass('Exception for non-retrievable schema found.');
    }
    $this->assertFalse(Database::getConnection()->schema()->tableExists($table), "'$table' database table not found.");
    $schema = drupal_get_module_schema($module, $table);
    $this->assertTrue($schema, "'$table' table schema found.");

    // Verify that the same table can be installed after enabling the module.
    $this->enableModules([$module]);
    $this->installSchema($module, $table);
    $this->assertTrue(Database::getConnection()->schema()->tableExists($table), "'$table' database table found.");
    $schema = drupal_get_module_schema($module, $table);
    $this->assertTrue($schema, "'$table' table schema found.");
  }

  /**
   * Tests expected behavior of installEntitySchema().
   */
  public function testInstallEntitySchema() {
    $entity = 'entity_test';
    // The entity_test Entity has a field that depends on the User module.
    $this->enableModules(['user']);
    // Verity that the entity schema is created properly.
    $this->installEntitySchema($entity);
    $this->assertTrue(Database::getConnection()->schema()->tableExists($entity), "'$entity' database table found.");
  }

  /**
   * Tests expected behavior of installConfig().
   */
  public function testInstallConfig() {
    // The user module has configuration that depends on system.
    $this->enableModules(['system']);
    $module = 'user';

    // Verify that default config can only be installed for enabled modules.
    try {
      $this->installConfig([$module]);
      $this->fail('Exception for non-enabled module found.');
    }
    catch (\Exception $e) {
      $this->pass('Exception for non-enabled module found.');
    }
    $this->assertFalse($this->container->get('config.storage')->exists('user.settings'));

    // Verify that default config can be installed.
    $this->enableModules(['user']);
    $this->installConfig(['user']);
    $this->assertTrue($this->container->get('config.storage')->exists('user.settings'));
    $this->assertTrue($this->config('user.settings')->get('register'));
  }

  /**
   * Tests that the module list is retained after enabling/installing/disabling.
   */
  public function testEnableModulesFixedList() {
    // Install system module.
    $this->container->get('module_installer')->install(['system', 'user', 'menu_link_content']);
    $entity_manager = \Drupal::entityManager();

    // entity_test is loaded via $modules; its entity type should exist.
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == $entity_manager->getDefinition('entity_test'));

    // Load some additional modules; entity_test should still exist.
    $this->enableModules(['field', 'text', 'entity_test']);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == $entity_manager->getDefinition('entity_test'));

    // Install some other modules; entity_test should still exist.
    $this->container->get('module_installer')->install(['user', 'field', 'field_test'], FALSE);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == $entity_manager->getDefinition('entity_test'));

    // Uninstall one of those modules; entity_test should still exist.
    $this->container->get('module_installer')->uninstall(['field_test']);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == $entity_manager->getDefinition('entity_test'));

    // Set the weight of a module; entity_test should still exist.
    module_set_weight('field', -1);
    $this->assertEqual($this->container->get('module_handler')->moduleExists('entity_test'), TRUE);
    $this->assertTrue(TRUE == $entity_manager->getDefinition('entity_test'));

    // Reactivate the previously uninstalled module.
    $this->enableModules(['field_test']);

    // Create a field.
    $this->installEntitySchema('entity_test');
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
    ]);
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'test_field',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests that ThemeManager works right after loading a module.
   */
  public function testEnableModulesTheme() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $original_element = $element = [
      '#type' => 'container',
      '#markup' => 'Foo',
      '#attributes' => [],
    ];
    $this->enableModules(['system']);
    // \Drupal\Core\Theme\ThemeManager::render() throws an exception if modules
    // are not loaded yet.
    $this->assertTrue($renderer->renderRoot($element));

    $element = $original_element;
    $this->disableModules(['entity_test']);
    $this->assertTrue($renderer->renderRoot($element));
  }

  /**
   * Tests that there is no theme by default.
   */
  public function testNoThemeByDefault() {
    $themes = $this->config('core.extension')->get('theme');
    $this->assertEqual($themes, []);

    $extensions = $this->container->get('config.storage')->read('core.extension');
    $this->assertEqual($extensions['theme'], []);

    $active_theme = $this->container->get('theme.manager')->getActiveTheme();
    $this->assertEqual($active_theme->getName(), 'core');
  }

  /**
   * Tests that \Drupal::installProfile() returns NULL.
   *
   * As the currently active installation profile is used when installing
   * configuration, for example, this is essential to ensure test isolation.
   */
  public function testDrupalGetProfile() {
    $this->assertNull(\Drupal::installProfile());
  }

  /**
   * {@inheritdoc}
   */
  public function run(array $methods = []) {
    parent::run($methods);

    // Check that all tables of the test instance have been deleted. At this
    // point the original database connection is restored so we need to prefix
    // the tables.
    $connection = Database::getConnection();
    if ($connection->databaseType() != 'sqlite') {
      $tables = $connection->schema()->findTables($this->databasePrefix . '%');
      $this->assertTrue(empty($tables), 'All test tables have been removed.');
    }
    else {
      // We don't have the test instance connection anymore so we have to
      // re-attach its database and then use the same query as
      // \Drupal\Core\Database\Driver\sqlite\Schema::findTables().
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::__construct()
      $info = Database::getConnectionInfo();
      $connection->query('ATTACH DATABASE :database AS :prefix', [
        ':database' => $info['default']['database'] . '-' . $this->databasePrefix,
        ':prefix' => $this->databasePrefix,
      ]);

      $result = $connection->query("SELECT name FROM " . $this->databasePrefix . ".sqlite_master WHERE type = :type AND name LIKE :table_name AND name NOT LIKE :pattern", [
        ':type' => 'table',
        ':table_name' => '%',
        ':pattern' => 'sqlite_%',
      ])->fetchAllKeyed(0, 0);

      $this->assertTrue(empty($result), 'All test tables have been removed.');
    }
  }

}
