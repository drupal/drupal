<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity\Sql;

use Drupal\Core\Database\Event\StatementEvent;
use Drupal\Core\Database\Log;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the deleteFromDedicatedTables() method only executes one DELETE query.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class SqlContentEntityStorageTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_rev');
  }

  /**
   * Tests that only one SQL DELETE is executed on dedicated data tables.
   */
  public function testDeleteFromDedicatedTablesExecutesOneDelete(): void {
    // The array of entity IDs to delete.
    $ids = [1, 2, 3];

    // Create a dummy field storage definition.
    $dummy_storage_definition = $this->getMockBuilder(FieldStorageDefinitionInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $fieldStorageDefinitions = [$dummy_storage_definition];

    // Create a dummy entity type that is revisionable.
    $entityType = $this->getMockBuilder(EntityTypeInterface::class)
      ->getMock();
    $entityType->method('isRevisionable')
      ->willReturn(TRUE);

    // Create a dummy table mapping that always requires dedicated table storage.
    $dummyTableMapping = $this->getMockBuilder(DefaultTableMapping::class)
      ->disableOriginalConstructor()
      ->getMock();
    $dummyTableMapping
      ->method('requiresDedicatedTableStorage')
      ->with($dummy_storage_definition)
      ->willReturn(TRUE);
    $dummyTableMapping
      ->method('getDedicatedDataTableName')
      ->with($dummy_storage_definition)
      ->willReturn('dedicated_table');
    $dummyTableMapping
      ->method('getDedicatedRevisionTableName')
      ->with($dummy_storage_definition)
      ->willReturn('dedicated_revision_table');

    // Create an instance of our testable storage subclass.
    $storage = new TestableSqlContentEntityStorage();
    // Use the real database connection from the container.
    $storage->database = $this->container->get('database');
    $storage->entityType = $entityType;
    $storage->fieldStorageDefinitions = $fieldStorageDefinitions;
    $storage->setTableMapping($dummyTableMapping);

    // Create the dummy dedicated data tables.
    $schema = $storage->database->schema();
    $schema->createTable('dedicated_table', [
      'fields' => [
        'entity_id' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
    ]);
    $schema->createTable('dedicated_revision_table', [
      'fields' => [
        'entity_id' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
        'vid' => [
          'type' => 'int',
          'not null' => TRUE,
        ],
      ],
    ]);

    // Set up a test query logger to capture executed queries.
    $logger = new Log();
    $storage->database->setLogger($logger);
    $storage->database->enableEvents(StatementEvent::all());

    // Call the method that exposes the protected deleteFromDedicatedTables() method.
    $logger->start('default');
    $storage->publicDeleteFromDedicatedTables($ids);
    $queries = $logger->get('default');
    $logger->end('default');

    // Filter queries to include only DELETE queries over each table.
    $dedicatedTableDeleteQueries = array_filter($queries, static function ($query) {
      return preg_match('/^DELETE FROM ".*dedicated_table"/', $query['query']);
    });
    $dedicatedRevisionTableDeleteQueries = array_filter($queries, static function ($query) {
      return preg_match('/^DELETE FROM ".*dedicated_revision_table"/', $query['query']);
    });

    // Assert that exactly one DELETE query was executed on each table.
    $this->assertCount(1, $dedicatedTableDeleteQueries, 'Only one DELETE query on the dedicated data table was executed.');
    $this->assertCount(1, $dedicatedRevisionTableDeleteQueries, 'Only one DELETE query on the dedicated revision data table was executed.');
  }

  /**
   * Tests that entities with a large number (65+) of fields load successfully.
   */
  #[DataProvider('providerCardinality')]
  public function testEntityWithManyFieldsLoad(int $cardinality): void {
    $fieldCount = 71;
    for ($i = 1; $i <= $fieldCount; $i++) {
      $fieldStorage = FieldStorageConfig::create([
        'field_name' => 'field_test_' . $i,
        'entity_type' => 'entity_test_rev',
        'type' => 'string',
        'cardinality' => $cardinality,
      ]);
      $fieldStorage->save();

      FieldConfig::create([
        'field_storage' => $fieldStorage,
        'bundle' => 'entity_test_rev',
      ])->save();
    }

    $values = [
      'name' => 'Test entity',
      'bundle' => 'entity_test_rev',
    ];
    for ($i = 1; $i <= $fieldCount; $i++) {
      $values['field_test_' . $i] = 'value_' . $i;
    }
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test_rev');
    $entity = $storage->create($values);
    $entity->save();
    $id = $entity->id();
    $storage->resetCache();
    $entity = $storage->load($id);
    for ($i = 1; $i <= $fieldCount; $i++) {
      $this->assertSame("value_$i", $entity->get("field_test_$i")->value);
    }
    $revision_id = $entity->getRevisionId();
    $storage->resetCache();
    $revision = $storage->loadRevision($revision_id);
    for ($i = 1; $i <= $fieldCount; $i++) {
      $this->assertSame("value_$i", $revision->get("field_test_$i")->value);
    }
  }

  /**
   * Data provider for testEntityWithManyFieldsLoad().
   *
   * @return array
   *   Test cases.
   */
  public static function providerCardinality(): array {
    return [
      'single cardinality' => [1],
      'unlimited cardinality' => [-1],
    ];
  }

}
