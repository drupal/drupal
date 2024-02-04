<?php

namespace Drupal\Tests\pgsql\Kernel\pgsql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;
use Drupal\KernelTests\Core\Database\DatabaseTestSchemaDataTrait;
use Drupal\KernelTests\Core\Database\DatabaseTestSchemaInstallTrait;
use Drupal\KernelTests\Core\Database\DriverSpecificKernelTestBase;

// cSpell:ignore nspname schemaname upserting indexdef

/**
 * Tests schema API for non-public schema for the PostgreSQL driver.
 *
 * @group Database
 * @coversDefaultClass \Drupal\pgsql\Driver\Database\pgsql\Schema
 */
class NonPublicSchemaTest extends DriverSpecificKernelTestBase {

  use DatabaseTestSchemaDataTrait;
  use DatabaseTestSchemaInstallTrait;

  /**
   * The database connection for testing.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $testingFakeConnection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a connection to the non-public schema.
    $info = Database::getConnectionInfo('default');
    $info['default']['schema'] = 'testing_fake';
    Database::getConnection()->query('CREATE SCHEMA IF NOT EXISTS testing_fake');
    Database::addConnectionInfo('default', 'testing_fake', $info['default']);

    $this->testingFakeConnection = Database::getConnection('testing_fake', 'default');

    $table_specification = [
      'description' => 'Schema table description may contain "quotes" and could be long—very long indeed.',
      'fields' => [
        'id'  => [
          'type' => 'serial',
          'not null' => TRUE,
        ],
        'test_field'  => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ];

    $this->testingFakeConnection->schema()->createTable('faking_table', $table_specification);

    $this->testingFakeConnection->insert('faking_table')
      ->fields(
        [
          'id' => '1',
          'test_field' => '123',
        ]
      )->execute();

    $this->testingFakeConnection->insert('faking_table')
      ->fields(
        [
          'id' => '2',
          'test_field' => '456',
        ]
      )->execute();

    $this->testingFakeConnection->insert('faking_table')
      ->fields(
        [
          'id' => '3',
          'test_field' => '789',
        ]
      )->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // We overwrite this function because the regular teardown will not drop the
    // tables from a specified schema.
    $tables = $this->testingFakeConnection->schema()->findTables('%');
    foreach ($tables as $table) {
      if ($this->testingFakeConnection->schema()->dropTable($table)) {
        unset($tables[$table]);
      }
    }

    $this->assertEmpty($this->testingFakeConnection->schema()->findTables('%'));

    Database::removeConnection('testing_fake');

    parent::tearDown();
  }

  /**
   * @covers ::extensionExists
   * @covers ::tableExists
   */
  public function testExtensionExists(): void {
    // Check if PG_trgm extension is present.
    $this->assertTrue($this->testingFakeConnection->schema()->extensionExists('pg_trgm'));
    // Asserting that the Schema testing_fake exist in the database.
    $this->assertCount(1, \Drupal::database()->query("SELECT * FROM pg_catalog.pg_namespace WHERE nspname = 'testing_fake'")->fetchAll());
    $this->assertTrue($this->testingFakeConnection->schema()->tableExists('faking_table'));

    // Hardcoded assertion that we created the table in the non-public schema.
    $this->assertCount(1, $this->testingFakeConnection->query("SELECT * FROM pg_tables WHERE schemaname = 'testing_fake' AND tablename = :prefixedTable", [':prefixedTable' => $this->testingFakeConnection->getPrefix() . "faking_table"])->fetchAll());
  }

  /**
   * @covers ::addField
   * @covers ::fieldExists
   * @covers ::dropField
   * @covers ::changeField
   */
  public function testField(): void {
    $this->testingFakeConnection->schema()->addField('faking_table', 'added_field', ['type' => 'int', 'not null' => FALSE]);
    $this->assertTrue($this->testingFakeConnection->schema()->fieldExists('faking_table', 'added_field'));

    $this->testingFakeConnection->schema()->changeField('faking_table', 'added_field', 'changed_field', ['type' => 'int', 'not null' => FALSE]);
    $this->assertFalse($this->testingFakeConnection->schema()->fieldExists('faking_table', 'added_field'));
    $this->assertTrue($this->testingFakeConnection->schema()->fieldExists('faking_table', 'changed_field'));

    $this->testingFakeConnection->schema()->dropField('faking_table', 'changed_field');
    $this->assertFalse($this->testingFakeConnection->schema()->fieldExists('faking_table', 'changed_field'));
  }

  /**
   * @covers \Drupal\Core\Database\Connection::insert
   * @covers \Drupal\Core\Database\Connection::select
   */
  public function testInsert(): void {
    $num_records_before = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();

    $this->testingFakeConnection->insert('faking_table')
      ->fields([
        'id' => '123',
        'test_field' => '55',
      ])->execute();

    // Testing that the insert is correct.
    $results = $this->testingFakeConnection->select('faking_table')->fields('faking_table')->condition('id', '123')->execute()->fetchAll();
    $this->assertIsArray($results);

    $num_records_after = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    $this->assertSame('123', $results[0]->id);
    $this->assertSame('55', $results[0]->test_field);
  }

  /**
   * @covers \Drupal\Core\Database\Connection::update
   */
  public function testUpdate(): void {
    $updated_record = $this->testingFakeConnection->update('faking_table')
      ->fields(['test_field' => 321])
      ->condition('id', 1)
      ->execute();

    $this->assertSame(1, $updated_record, 'Updated 1 record.');

    $updated_results = $this->testingFakeConnection->select('faking_table')->fields('faking_table')->condition('id', '1')->execute()->fetchAll();

    $this->assertSame('321', $updated_results[0]->test_field);
  }

  /**
   * @covers \Drupal\Core\Database\Connection::upsert
   */
  public function testUpsert(): void {
    $num_records_before = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();

    $upsert = $this->testingFakeConnection->upsert('faking_table')
      ->key('id')
      ->fields(['id', 'test_field']);

    // Upserting a new row.
    $upsert->values([
      'id' => '456',
      'test_field' => '444',
    ]);

    // Upserting an existing row.
    $upsert->values([
      'id' => '1',
      'test_field' => '898',
    ]);

    $result = $upsert->execute();
    $this->assertSame(2, $result, 'The result of the upsert operation should report that at exactly two rows were affected.');

    $num_records_after = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    // Check if new row has been added with upsert.
    $result = $this->testingFakeConnection->query('SELECT * FROM {faking_table} WHERE [id] = :id', [':id' => '456'])->fetch();
    $this->assertEquals('456', $result->id, 'ID set correctly.');
    $this->assertEquals('444', $result->test_field, 'test_field set correctly.');

    // Check if new row has been edited with upsert.
    $result = $this->testingFakeConnection->query('SELECT * FROM {faking_table} WHERE [id] = :id', [':id' => '1'])->fetch();
    $this->assertEquals('1', $result->id, 'ID set correctly.');
    $this->assertEquals('898', $result->test_field, 'test_field set correctly.');
  }

  /**
   * @covers \Drupal\Core\Database\Connection::merge
   */
  public function testMerge(): void {
    $num_records_before = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();

    $this->testingFakeConnection->merge('faking_table')
      ->key('id', '789')
      ->fields([
        'test_field' => 343,
      ])
      ->execute();

    $num_records_after = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();
    $this->assertEquals($num_records_before + 1, $num_records_after, 'Merge inserted properly.');

    $merge_results = $this->testingFakeConnection->select('faking_table')->fields('faking_table')->condition('id', '789')->execute()->fetchAll();
    $this->assertSame('789', $merge_results[0]->id);
    $this->assertSame('343', $merge_results[0]->test_field);
  }

  /**
   * @covers \Drupal\Core\Database\Connection::delete
   * @covers \Drupal\Core\Database\Connection::truncate
   */
  public function testDelete(): void {
    $num_records_before = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();

    $num_deleted = $this->testingFakeConnection->delete('faking_table')
      ->condition('id', 3)
      ->execute();
    $this->assertSame(1, $num_deleted, 'Deleted 1 record.');

    $num_records_after = $this->testingFakeConnection->query('SELECT COUNT(*) FROM {faking_table}')->fetchField();
    $this->assertEquals($num_records_before, $num_records_after + $num_deleted, 'Deletion adds up.');

    $num_records_before = $this->testingFakeConnection->query("SELECT COUNT(*) FROM {faking_table}")->fetchField();
    $this->assertNotEmpty($num_records_before);

    $this->testingFakeConnection->truncate('faking_table')->execute();

    $num_records_after = $this->testingFakeConnection->query("SELECT COUNT(*) FROM {faking_table}")->fetchField();
    $this->assertEquals(0, $num_records_after, 'Truncate really deletes everything.');
  }

  /**
   * @covers ::addIndex
   * @covers ::indexExists
   * @covers ::dropIndex
   */
  public function testIndex(): void {
    $this->testingFakeConnection->schema()->addIndex('faking_table', 'test_field', ['test_field'], []);

    $this->assertTrue($this->testingFakeConnection->schema()->indexExists('faking_table', 'test_field'));

    $results = $this->testingFakeConnection->query("SELECT * FROM pg_indexes WHERE indexname = :indexname", [':indexname' => $this->testingFakeConnection->getPrefix() . 'faking_table__test_field__idx'])->fetchAll();

    $this->assertCount(1, $results);
    $this->assertSame('testing_fake', $results[0]->schemaname);
    $this->assertSame($this->testingFakeConnection->getPrefix() . 'faking_table', $results[0]->tablename);
    $this->assertStringContainsString('USING btree (test_field)', $results[0]->indexdef);

    $this->testingFakeConnection->schema()->dropIndex('faking_table', 'test_field');

    $this->assertFalse($this->testingFakeConnection->schema()->indexExists('faking_table', 'test_field'));
  }

  /**
   * @covers ::addUniqueKey
   * @covers ::indexExists
   * @covers ::dropUniqueKey
   */
  public function testUniqueKey(): void {
    $this->testingFakeConnection->schema()->addUniqueKey('faking_table', 'test_field', ['test_field']);

    // This should work, but currently indexExist() only searches for keys that end with idx.
    // @todo remove comments when: https://www.drupal.org/project/drupal/issues/3325358 is committed.
    // $this->assertTrue($this->testingFakeConnection->schema()->indexExists('faking_table', 'test_field'));

    $results = $this->testingFakeConnection->query("SELECT * FROM pg_indexes WHERE indexname = :indexname", [':indexname' => $this->testingFakeConnection->getPrefix() . 'faking_table__test_field__key'])->fetchAll();

    // Check the unique key columns.
    $this->assertCount(1, $results);
    $this->assertSame('testing_fake', $results[0]->schemaname);
    $this->assertSame($this->testingFakeConnection->getPrefix() . 'faking_table', $results[0]->tablename);
    $this->assertStringContainsString('USING btree (test_field)', $results[0]->indexdef);

    $this->testingFakeConnection->schema()->dropUniqueKey('faking_table', 'test_field');

    // This function will not work due to a the fact that indexExist() does not search for keys without idx tag.
    // @todo remove comments when: https://www.drupal.org/project/drupal/issues/3325358 is committed.
    // $this->assertFalse($this->testingFakeConnection->schema()->indexExists('faking_table', 'test_field'));
  }

  /**
   * @covers ::addPrimaryKey
   * @covers ::dropPrimaryKey
   */
  public function testPrimaryKey(): void {
    $this->testingFakeConnection->schema()->dropPrimaryKey('faking_table');
    $results = $this->testingFakeConnection->query("SELECT * FROM pg_indexes WHERE schemaname = 'testing_fake'")->fetchAll();

    $this->assertCount(0, $results);

    $this->testingFakeConnection->schema()->addPrimaryKey('faking_table', ['id']);
    $results = $this->testingFakeConnection->query("SELECT * FROM pg_indexes WHERE schemaname = 'testing_fake'")->fetchAll();

    $this->assertCount(1, $results);
    $this->assertSame('testing_fake', $results[0]->schemaname);
    $this->assertSame($this->testingFakeConnection->getPrefix() . 'faking_table', $results[0]->tablename);
    $this->assertStringContainsString('USING btree (id)', $results[0]->indexdef);

    $find_primary_keys_columns = new \ReflectionMethod(get_class($this->testingFakeConnection->schema()), 'findPrimaryKeyColumns');
    $results = $find_primary_keys_columns->invoke($this->testingFakeConnection->schema(), 'faking_table');

    $this->assertCount(1, $results);
    $this->assertSame('id', $results[0]);
  }

  /**
   * @covers ::renameTable
   * @covers ::tableExists
   * @covers ::findTables
   * @covers ::dropTable
   */
  public function testTable(): void {
    $this->testingFakeConnection->schema()->renameTable('faking_table', 'new_faking_table');

    $tables = $this->testingFakeConnection->schema()->findTables('%');
    $result = $this->testingFakeConnection->query("SELECT * FROM information_schema.tables WHERE table_schema = 'testing_fake'")->fetchAll();
    $this->assertFalse($this->testingFakeConnection->schema()->tableExists('faking_table'));
    $this->assertTrue($this->testingFakeConnection->schema()->tableExists('new_faking_table'));
    $this->assertEquals($this->testingFakeConnection->getPrefix() . 'new_faking_table', $result[0]->table_name);
    $this->assertEquals('testing_fake', $result[0]->table_schema);
    sort($tables);
    $this->assertEquals(['new_faking_table'], $tables);

    $this->testingFakeConnection->schema()->dropTable('new_faking_table');
    $this->assertFalse($this->testingFakeConnection->schema()->tableExists('new_faking_table'));
    $this->assertCount(0, $this->testingFakeConnection->query("SELECT * FROM information_schema.tables WHERE table_schema = 'testing_fake'")->fetchAll());
  }

}
