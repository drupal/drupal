<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity\Sql;

use Drupal\Core\Database\Event\StatementEvent;
use Drupal\Core\Entity\Sql\DefaultTableMapping;
use Drupal\Core\Database\Log;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the deleteFromDedicatedTables() method only executes one DELETE query.
 *
 * @group Entity
 */
class SqlContentEntityStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

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

}
