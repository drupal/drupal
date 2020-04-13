<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * Tests the migration plugin manager.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\MigratePluginManager
 * @group migrate
 */
class MigrationPluginListTest extends KernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    // Test with all modules containing Drupal migrations.
    'action',
    'aggregator',
    'ban',
    'block',
    'block_content',
    'book',
    'comment',
    'contact',
    'content_translation',
    'dblog',
    'field',
    'file',
    'filter',
    'forum',
    'image',
    'language',
    'locale',
    'menu_link_content',
    'menu_ui',
    'node',
    'options',
    'path',
    'search',
    'shortcut',
    'statistics',
    'syslog',
    'system',
    'taxonomy',
    'text',
    'tracker',
    'update',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
  }

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions() {
    // Create an entity reference field to make sure that migrations derived by
    // EntityReferenceTranslationDeriver do not get discovered without
    // migrate_drupal enabled.
    $this->createEntityReferenceField('user', 'user', 'field_entity_reference', 'Entity Reference', 'node');

    // Make sure retrieving all the core migration plugins does not throw any
    // errors.
    $migration_plugins = $this->container->get('plugin.manager.migration')->getDefinitions();
    // All the plugins provided by core depend on migrate_drupal.
    $this->assertEmpty($migration_plugins);

    // Enable a module that provides migrations that do not depend on
    // migrate_drupal.
    $this->enableModules(['migrate_external_translated_test']);
    $migration_plugins = $this->container->get('plugin.manager.migration')->getDefinitions();
    // All the plugins provided by migrate_external_translated_test do not
    // depend on migrate_drupal.
    $this::assertArrayHasKey('external_translated_test_node', $migration_plugins);
    $this::assertArrayHasKey('external_translated_test_node_translation', $migration_plugins);

    // Disable the test module and the list should be empty again.
    $this->disableModules(['migrate_external_translated_test']);
    $migration_plugins = $this->container->get('plugin.manager.migration')->getDefinitions();
    // All the plugins provided by core depend on migrate_drupal.
    $this->assertEmpty($migration_plugins);

    // Enable migrate_drupal to test that the plugins can now be discovered.
    $this->enableModules(['migrate_drupal']);

    // Make sure retrieving these migration plugins in the absence of a database
    // connection does not throw any errors.
    $migration_plugins = $this->container->get('plugin.manager.migration')->createInstances([]);
    // Any database-based source plugins should fail a requirements test in the
    // absence of a source database connection (e.g., a connection with the
    // 'migrate' key).
    $source_plugins = array_map(function ($migration_plugin) {
      return $migration_plugin->getSourcePlugin();
    }, $migration_plugins);
    foreach ($source_plugins as $id => $source_plugin) {
      if ($source_plugin instanceof RequirementsInterface) {
        try {
          $source_plugin->checkRequirements();
        }
        catch (RequirementsException $e) {
          unset($source_plugins[$id]);
        }
      }
    }

    // Without a connection defined, no database-based plugins should be
    // returned.
    foreach ($source_plugins as $id => $source_plugin) {
      $this->assertNotInstanceOf(SqlBase::class, $source_plugin);
    }

    // Set up a migrate database connection so that plugin discovery works.
    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo('migrate');
    if ($connection_info) {
      Database::renameConnection('migrate', 'simpletest_original_migrate');
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = is_array($value['prefix']) ? $value['prefix']['default'] : $value['prefix'];
      // Simpletest uses 7 character prefixes at most so this can't cause
      // collisions.
      $connection_info[$target]['prefix']['default'] = $prefix . '0';

      // Add the original simpletest prefix so SQLite can attach its database.
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::init()
      $connection_info[$target]['prefix'][$value['prefix']['default']] = $value['prefix']['default'];
    }
    Database::addConnectionInfo('migrate', 'default', $connection_info['default']);

    $migration_plugins = $this->container->get('plugin.manager.migration')->getDefinitions();
    // All the plugins provided by core depend on migrate_drupal.
    $this->assertNotEmpty($migration_plugins);

    // Test that migrations derived by EntityReferenceTranslationDeriver are
    // discovered now that migrate_drupal is enabled.
    $this->assertArrayHasKey('d6_entity_reference_translation:user__user', $migration_plugins);
    $this->assertArrayHasKey('d7_entity_reference_translation:user__user', $migration_plugins);
  }

}
