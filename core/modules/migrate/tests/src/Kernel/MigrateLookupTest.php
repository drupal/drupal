<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\MigrateException;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the Migrate Lookup service.
 *
 * @group migrate
 */
class MigrateLookupTest extends MigrateTestBase {

  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'field',
    'user',
    'text',
    'migrate_lookup_test',
  ];

  /**
   * The migration lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setTestLogger();
    $this->migrateLookup = $this->container->get('migrate.lookup');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['node', 'user']);
    $this->createContentType(['type' => 'node_lookup']);
  }

  /**
   * Tests scenarios around single id lookups.
   */
  public function testSingleLookup() {
    $this->executeMigration('sample_lookup_migration');

    // Test numerically indexed source id.
    $result = $this->migrateLookup->lookup('sample_lookup_migration', [17]);
    $this->assertSame('1', $result[0]['nid']);

    // Test associatively indexed source id.
    $result = $this->migrateLookup->lookup('sample_lookup_migration', ['id' => 25]);
    $this->assertSame('2', $result[0]['nid']);

    // Test lookup not found.
    $result = $this->migrateLookup->lookup('sample_lookup_migration', [1337]);
    $this->assertSame([], $result);
  }

  /**
   * Tests an invalid lookup.
   */
  public function testInvalidIdLookup() {
    $this->executeMigration('sample_lookup_migration');
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Extra unknown items for map migrate_map_sample_lookup_migration in source IDs: array (\n  'invalid_id' => 25,\n)");

    // Test invalidly indexed source id.
    $this->migrateLookup->lookup('sample_lookup_migration', ['invalid_id' => 25]);
  }

  /**
   * Tests lookups with multiple source ids.
   */
  public function testMultipleSourceIds() {
    $this->executeMigration('sample_lookup_migration_multiple_source_ids');

    // Test with full set of numerically indexed source ids.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_multiple_source_ids', [
      25,
      26,
    ]);
    $this->assertCount(1, $result);
    $this->assertSame('3', $result[0]['nid']);

    // Test with full set of associatively indexed source ids.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_multiple_source_ids', [
      'id' => 17,
      'version_id' => 17,
    ]);
    $this->assertCount(1, $result);
    $this->assertSame('1', $result[0]['nid']);

    // Test with full set of associatively indexed source ids in the wrong
    // order.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_multiple_source_ids', [
      'version_id' => 26,
      'id' => 25,
    ]);
    $this->assertCount(1, $result);
    $this->assertSame('3', $result[0]['nid']);

    // Test with a partial set of numerically indexed ids.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_multiple_source_ids', [25]);
    $this->assertCount(2, $result);
    $this->assertSame('2', $result[0]['nid']);
    $this->assertSame('3', $result[1]['nid']);

    // Test with a partial set of associatively indexed ids.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_multiple_source_ids', ['version_id' => 25]);
    $this->assertCount(1, $result);
    $this->assertSame('2', $result[0]['nid']);
  }

  /**
   * Tests looking up against multiple migrations at once.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\migrate\MigrateException
   */
  public function testMultipleMigrationLookup() {
    $migrations = [
      'sample_lookup_migration',
      'sample_lookup_migration_2',
    ];
    foreach ($migrations as $migration) {
      $this->executeMigration($migration);
    }

    // Test numerically indexed source id.
    $result = $this->migrateLookup->lookup($migrations, [17]);
    $this->assertSame('1', $result[0]['nid']);

    // Test associatively indexed source id.
    $result = $this->migrateLookup->lookup($migrations, ['id' => 35]);
    $this->assertSame('4', $result[0]['nid']);

    // Test lookup not found.
    $result = $this->migrateLookup->lookup($migrations, [1337]);
    $this->assertSame([], $result);
  }

  /**
   * Tests a lookup with string source ids.
   */
  public function testLookupWithStringIds() {
    $this->executeMigration('sample_lookup_migration_string_ids');

    // Test numerically indexed source id.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_string_ids', ['node1']);
    $this->assertSame('10', $result[0]['nid']);

    // Test associatively indexed source id.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_string_ids', ['id' => 'node2']);
    $this->assertSame('11', $result[0]['nid']);

    // Test lookup not found.
    $result = $this->migrateLookup->lookup('sample_lookup_migration_string_ids', ['node1337']);
    $this->assertSame([], $result);
  }

}
