<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Update;

use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;

/**
 * Tests the update path base class.
 *
 * @group Update
 * @group #slow
 */
class UpdatePathTestBaseTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_schema'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz';
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../modules/system/tests/fixtures/update/drupal-8.update-test-schema-enabled.php';
    $this->databaseDumpFiles[] = __DIR__ . '/../../../../modules/system/tests/fixtures/update/drupal-8.update-test-semver-update-n-enabled.php';
  }

  /**
   * Tests that the database was properly loaded.
   */
  public function testDatabaseProperlyLoaded(): void {
    $this->testDatabaseLoaded();
  }

  /**
   * Tests that updates are properly run.
   */
  public function testUpdateHookN(): void {
    $connection = Database::getConnection();

    // Increment the schema version.
    \Drupal::state()->set('update_test_schema_version', 8001);
    $this->runUpdates();

    // Ensure that after running the updates the update functions have been
    // loaded. If they have not then the tests carried out in
    // \Drupal\Tests\UpdatePathTestTrait::runUpdates() can result in false
    // positives.
    $this->assertTrue(function_exists('update_test_semver_update_n_update_8001'), 'The update_test_semver_update_n_update_8001() has been loaded');

    $select = $connection->select('watchdog');
    $select->orderBy('wid', 'DESC');
    $select->range(0, 5);
    $select->fields('watchdog', ['message']);

    $container_cannot_be_saved_messages = array_filter(iterator_to_array($select->execute()), function ($row) {
      return str_contains($row->message, 'Container cannot be saved to cache.');
    });
    $this->assertEquals([], $container_cannot_be_saved_messages);

    // Ensure schema has changed.
    /** @var \Drupal\Core\Update\UpdateHookRegistry $update_registry */
    $update_registry = \Drupal::service('update.update_hook_registry');
    $this->assertEquals(8001, $update_registry->getInstalledVersion('update_test_schema'));
    $this->assertEquals(8001, $update_registry->getInstalledVersion('update_test_semver_update_n'));
    // Ensure the index was added for column a.
    $this->assertTrue($connection->schema()->indexExists('update_test_schema_table', 'test'), 'Version 8001 of the update_test_schema module is installed.');
    // Ensure update_test_semver_update_n_update_8001 was run.
    $this->assertEquals('Yes, I was run. Thanks for testing!', \Drupal::state()->get('update_test_semver_update_n_update_8001'));
  }

  /**
   * Tests that path aliases are not processed during database updates.
   */
  public function testPathAliasProcessing(): void {
    // Add a path alias for the '/admin' system path.
    $values = [
      'path' => '/admin/structure',
      'alias' => '/admin-structure-alias',
      'langcode' => 'und',
      'status' => 1,
    ];

    $database = \Drupal::database();
    $id = $database->insert('path_alias')
      ->fields($values + ['uuid' => \Drupal::service('uuid')->generate()])
      ->execute();

    $revision_id = $database->insert('path_alias_revision')
      ->fields($values + ['id' => $id, 'revision_default' => 1])
      ->execute();

    $database->update('path_alias')
      ->fields(['revision_id' => $revision_id])
      ->condition('id', $id)
      ->execute();

    // Increment the schema version.
    \Drupal::state()->set('update_test_schema_version', 8002);
    $this->runUpdates();

    // Check that the alias defined earlier is not used during the update
    // process.
    $this->assertSession()->linkByHrefExists('/admin/structure');
    $this->assertSession()->linkByHrefNotExists('/admin-structure-alias');

    $account = $this->createUser(['administer site configuration', 'access administration pages', 'access site reports']);
    $this->drupalLogin($account);

    // Go to the status report page and check that the alias is used.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->linkByHrefNotExists('/admin/structure');
    $this->assertSession()->linkByHrefExists('/admin-structure-alias');
  }

  /**
   * Tests that test running environment is updated when module list changes.
   *
   * @see update_test_schema_update_8003()
   */
  public function testModuleListChange(): void {
    // Set a value in the cache to prove caches are cleared.
    \Drupal::service('cache.default')->set(__CLASS__, 'Test');

    // Ensure that modules are installed and uninstalled as expected prior to
    // running updates.
    $extension_config = $this->config('core.extension')->get();
    $this->assertArrayHasKey('page_cache', $extension_config['module']);
    $this->assertArrayNotHasKey('module_test', $extension_config['module']);

    $module_list = \Drupal::moduleHandler()->getModuleList();
    $this->assertArrayHasKey('page_cache', $module_list);
    $this->assertArrayNotHasKey('module_test', $module_list);

    $namespaces = \Drupal::getContainer()->getParameter('container.namespaces');
    $this->assertArrayHasKey('Drupal\page_cache', $namespaces);
    $this->assertArrayNotHasKey('Drupal\module_test', $namespaces);

    // Increment the schema version so that update_test_schema_update_8003()
    // runs.
    \Drupal::state()->set('update_test_schema_version', 8003);
    $this->runUpdates();

    // Ensure that test running environment has been updated with the changes to
    // the module list.
    $extension_config = $this->config('core.extension')->get();
    $this->assertArrayNotHasKey('page_cache', $extension_config['module']);
    $this->assertArrayHasKey('module_test', $extension_config['module']);

    $module_list = \Drupal::moduleHandler()->getModuleList();
    $this->assertArrayNotHasKey('page_cache', $module_list);
    $this->assertArrayHasKey('module_test', $module_list);

    $namespaces = \Drupal::getContainer()->getParameter('container.namespaces');
    $this->assertArrayNotHasKey('Drupal\page_cache', $namespaces);
    $this->assertArrayHasKey('Drupal\module_test', $namespaces);

    // Ensure the test runners cache has been cleared.
    $this->assertFalse(\Drupal::service('cache.default')->get(__CLASS__));
  }

  /**
   * Tests that schema can be excluded from testing.
   *
   * @see \Drupal\FunctionalTests\Update\UpdatePathTestBase::runUpdates()
   * @see \Drupal\Core\Test\TestSetupTrait::$configSchemaCheckerExclusions
   */
  public function testSchemaChecking(): void {
    // Create some configuration that should be skipped.
    $this->config('config_schema_test.no_schema')->set('foo', 'bar')->save();
    $this->runUpdates();
    $this->assertSame('bar', $this->config('config_schema_test.no_schema')->get('foo'));

  }

  /**
   * Tests that setup is done correctly.
   */
  public function testSetup(): void {
    $this->assertCount(3, $this->databaseDumpFiles);
    $this->assertSame(1, Settings::get('entity_update_batch_size'));
  }

}
