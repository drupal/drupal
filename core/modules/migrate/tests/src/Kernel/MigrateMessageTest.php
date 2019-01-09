<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateIdMapMessageEvent;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;

/**
 * Tests whether idmap messages are sent to message interface when requested.
 *
 * @group migrate
 */
class MigrateMessageTest extends KernelTestBase implements MigrateMessageInterface {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['migrate', 'system'];

  /**
   * Migration to run.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Messages accumulated during the migration run.
   *
   * @var array
   */
  protected $messages = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['system']);

    // A simple migration, which will generate a message to the ID map because
    // the concat plugin throws an exception if its source is not an array.
    $definition = [
      'migration_tags' => ['Message test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['name' => 'source_message', 'value' => 'a message'],
        ],
        'ids' => [
          'name' => ['type' => 'string'],
        ],
      ],
      'process' => [
        'message' => [
          'plugin' => 'concat',
          'source' => 'value',
        ],
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'system.maintenance',
      ],
    ];

    $this->migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
  }

  /**
   * Tests migration interruptions.
   */
  public function testMessagesNotTeed() {
    // We don't ask for messages to be teed, so don't expect any.
    $executable = new MigrateExecutable($this->migration, $this);
    $executable->import();
    $this->assertIdentical(count($this->messages), 0);
  }

  /**
   * Tests migration interruptions.
   */
  public function testMessagesTeed() {
    // Ask to receive any messages sent to the idmap.
    \Drupal::service('event_dispatcher')->addListener(MigrateEvents::IDMAP_MESSAGE,
      [$this, 'mapMessageRecorder']);
    $executable = new MigrateExecutable($this->migration, $this);
    $executable->import();
    $this->assertIdentical(count($this->messages), 1);
    $this->assertIdentical(reset($this->messages), "source_message: 'a message' is not an array");
  }

  /**
   * Reacts to map message event.
   *
   * @param \Drupal\migrate\Event\MigrateIdMapMessageEvent $event
   *   The migration event.
   * @param string $name
   *   The event name.
   */
  public function mapMessageRecorder(MigrateIdMapMessageEvent $event, $name) {
    if ($event->getLevel() == MigrationInterface::MESSAGE_NOTICE ||
        $event->getLevel() == MigrationInterface::MESSAGE_INFORMATIONAL) {
      $type = 'status';
    }
    else {
      $type = 'error';
    }
    $source_id_string = implode(',', $event->getSourceIdValues());
    $this->display($source_id_string . ': ' . $event->getMessage(), $type);
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->messages[] = $message;
  }

}
