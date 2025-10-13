<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the Row class.
 */
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class RowTest extends KernelTestBase {

  /**
   * The event dispatcher.
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The migration manager.
   */
  protected MigrationPluginManagerInterface $migrationManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'field',
    'migrate',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->migrationManager = \Drupal::service('plugin.manager.migration');

    // Create two fields that will be set during migration.
    $fields = ['field1', 'field2'];
    foreach ($fields as $field) {
      $this->entityTypeManager->getStorage('field_storage_config')->create([
        'entity_type' => 'entity_test',
        'field_name' => $field,
        'type' => 'string',
      ])->save();
      $this->entityTypeManager->getStorage('field_config')->create([
        'entity_type' => 'entity_test',
        'field_name' => $field,
        'bundle' => 'entity_test',
      ])->save();
    }
  }

  /**
   * Tests the destination properties of the Row class.
   */
  public function testRowDestinations(): void {
    $storage = $this->entityTypeManager->getStorage('entity_test');

    // Execute a migration that creates an entity with two fields.
    $data_rows = [
      ['id' => 1, 'field1' => 'f1value', 'field2' => 'f2value'],
    ];
    $ids = ['id' => ['type' => 'integer']];
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $data_rows,
        'ids' => $ids,
      ],
      'process' => [
        'id' => 'id',
        'field1' => 'field1',
        'field2' => 'field2',
      ],
      'destination' => ['plugin' => 'entity:entity_test'],
    ];
    $this->executeMigrationImport($definition);
    $entity = $storage->load(1);
    $this->assertEquals('f1value', $entity->get('field1')->getValue()[0]['value']);
    $this->assertEquals('f2value', $entity->get('field2')->getValue()[0]['value']);

    // Execute a second migration that attempts to remove both field values.
    // The event listener prevents the removal of the second field.
    $data_rows = [
      ['id' => 1, 'field1' => NULL, 'field2' => NULL],
    ];
    $definition['source']['data_rows'] = $data_rows;
    $this->eventDispatcher->addListener(MigrateEvents::PRE_ROW_SAVE, [$this, 'preventFieldRemoval']);
    $this->executeMigrationImport($definition);

    // The first field is now empty but the second field is still set.
    $entity = $storage->load(1);
    $this->assertTrue($entity->get('field1')->isEmpty());
    $this->assertEquals('f2value', $entity->get('field2')->getValue()[0]['value']);
  }

  /**
   * The pre-row-save event handler for the second migration.
   *
   * Checks row destinations and prevents the removal of the second field.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function preventFieldRemoval(MigratePreRowSaveEvent $event, string $name): void {
    $row = $event->getRow();

    // Both fields are empty and their existing values will be removed.
    $this->assertFalse($row->hasDestinationProperty('field1'));
    $this->assertFalse($row->hasDestinationProperty('field2'));
    $this->assertTrue($row->hasEmptyDestinationProperty('field1'));
    $this->assertTrue($row->hasEmptyDestinationProperty('field2'));

    // Prevent removal of field 2.
    $row->removeEmptyDestinationProperty('field2');
  }

  /**
   * Executes a migration import for the given migration definition.
   *
   * @param array $definition
   *   The migration definition.
   */
  protected function executeMigrationImport(array $definition): void {
    $migration = $this->migrationManager->createStubMigration($definition);
    (new MigrateExecutable($migration))->import();
  }

}
