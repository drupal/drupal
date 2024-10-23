<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\MigrateExecutable;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests interruptions triggered during migrations.
 *
 * @group migrate
 */
class MigrateInterruptionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_events_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_ROW_SAVE,
      [$this, 'postRowSaveEventRecorder']);
  }

  /**
   * Tests migration interruptions.
   */
  public function testMigrateEvents(): void {
    // Run a simple little migration, which should trigger one of each event
    // other than map_delete.
    $definition = [
      'migration_tags' => ['Interruption test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['data' => 'dummy value'],
          ['data' => 'dummy value2'],
        ],
        'ids' => [
          'data' => ['type' => 'string'],
        ],
      ],
      'process' => ['value' => 'data'],
      'destination' => ['plugin' => 'dummy'],
    ];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration);
    // When the import runs, the first row imported will trigger an
    // interruption.
    $result = $executable->import();

    $this->assertEquals(MigrationInterface::RESULT_INCOMPLETE, $result);

    // The status should have been reset to IDLE.
    $this->assertEquals(MigrationInterface::STATUS_IDLE, $migration->getStatus());
  }

  /**
   * Reacts to post-row-save event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function postRowSaveEventRecorder(MigratePostRowSaveEvent $event, $name): void {
    $event->getMigration()->interruptMigration(MigrationInterface::RESULT_INCOMPLETE);
  }

}
