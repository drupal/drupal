<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\MigrateExecutable;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests events fired on migrations.
 *
 * @group migrate
 */
class MigrateEventsTest extends KernelTestBase {

  /**
   * State service for recording information received by event listeners.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_events_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->state = \Drupal::state();
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::MAP_SAVE,
      [$this, 'mapSaveEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::MAP_DELETE,
      [$this, 'mapDeleteEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::PRE_IMPORT,
      [$this, 'preImportEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_IMPORT,
      [$this, 'postImportEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::PRE_ROW_SAVE,
      [$this, 'preRowSaveEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::POST_ROW_SAVE,
      [$this, 'postRowSaveEventRecorder']);
  }

  /**
   * Tests migration events.
   */
  public function testMigrateEvents(): void {
    // Run a simple little migration, which should trigger one of each event
    // other than map_delete.
    $definition = [
      'migration_tags' => ['Event test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['data' => 'dummy value'],
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
    // As the import runs, events will be dispatched, recording the received
    // information in state.
    $executable->import();

    // Validate from the recorded state that the events were received.
    $event = $this->state->get('migrate_events_test.pre_import_event', []);
    $this->assertSame(MigrateEvents::PRE_IMPORT, $event['event_name']);
    $this->assertSame($migration->id(), $event['migration']->id());

    $event = $this->state->get('migrate_events_test.post_import_event', []);
    $this->assertSame(MigrateEvents::POST_IMPORT, $event['event_name']);
    $this->assertSame($migration->id(), $event['migration']->id());

    $event = $this->state->get('migrate_events_test.map_save_event', []);
    $this->assertSame(MigrateEvents::MAP_SAVE, $event['event_name']);
    // Validating the last row processed.
    $this->assertSame('dummy value', $event['fields']['sourceid1']);
    $this->assertSame('dummy value', $event['fields']['destid1']);
    $this->assertSame(0, $event['fields']['source_row_status']);

    $event = $this->state->get('migrate_events_test.map_delete_event', []);
    $this->assertSame([], $event);

    $event = $this->state->get('migrate_events_test.pre_row_save_event', []);
    $this->assertSame(MigrateEvents::PRE_ROW_SAVE, $event['event_name']);
    $this->assertSame($migration->id(), $event['migration']->id());
    // Validating the last row processed.
    $this->assertSame('dummy value', $event['row']->getSourceProperty('data'));

    $event = $this->state->get('migrate_events_test.post_row_save_event', []);
    $this->assertSame(MigrateEvents::POST_ROW_SAVE, $event['event_name']);
    $this->assertSame($migration->id(), $event['migration']->id());
    // Validating the last row processed.
    $this->assertSame('dummy value', $event['row']->getSourceProperty('data'));
    $this->assertSame('dummy value', $event['destination_id_values']['value']);

    // Generate a map delete event.
    $migration->getIdMap()->delete(['data' => 'dummy value']);
    $event = $this->state->get('migrate_events_test.map_delete_event', []);
    $this->assertSame(MigrateEvents::MAP_DELETE, $event['event_name']);
    $this->assertSame(['data' => 'dummy value'], $event['source_id']);
  }

  /**
   * Reacts to map save event.
   *
   * @param \Drupal\migrate\Event\MigrateMapSaveEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function mapSaveEventRecorder(MigrateMapSaveEvent $event, $name) {
    $this->state->set('migrate_events_test.map_save_event', [
      'event_name' => $name,
      'map' => $event->getMap(),
      'fields' => $event->getFields(),
    ]);
  }

  /**
   * Reacts to map delete event.
   *
   * @param \Drupal\migrate\Event\MigrateMapDeleteEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function mapDeleteEventRecorder(MigrateMapDeleteEvent $event, $name) {
    $this->state->set('migrate_events_test.map_delete_event', [
      'event_name' => $name,
      'map' => $event->getMap(),
      'source_id' => $event->getSourceId(),
    ]);
  }

  /**
   * Reacts to pre-import event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function preImportEventRecorder(MigrateImportEvent $event, $name) {
    $this->state->set('migrate_events_test.pre_import_event', [
      'event_name' => $name,
      'migration' => $event->getMigration(),
    ]);
  }

  /**
   * Reacts to post-import event.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function postImportEventRecorder(MigrateImportEvent $event, $name) {
    $this->state->set('migrate_events_test.post_import_event', [
      'event_name' => $name,
      'migration' => $event->getMigration(),
    ]);
  }

  /**
   * Reacts to pre-row-save event.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function preRowSaveEventRecorder(MigratePreRowSaveEvent $event, $name) {
    $this->state->set('migrate_events_test.pre_row_save_event', [
      'event_name' => $name,
      'migration' => $event->getMigration(),
      'row' => $event->getRow(),
    ]);
  }

  /**
   * Reacts to post-row-save event.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function postRowSaveEventRecorder(MigratePostRowSaveEvent $event, $name) {
    $this->state->set('migrate_events_test.post_row_save_event', [
      'event_name' => $name,
      'migration' => $event->getMigration(),
      'row' => $event->getRow(),
      'destination_id_values' => $event->getDestinationIdValues(),
    ]);
  }

}
