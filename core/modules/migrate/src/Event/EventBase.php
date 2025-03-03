<?php

namespace Drupal\migrate\Event;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\Component\EventDispatcher\Event;

/**
 * Base class for migration events.
 */
class EventBase extends Event {

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The current message service.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   */
  protected $message;

  /**
   * Constructs a Migrate event object.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration being run.
   * @param \Drupal\migrate\MigrateMessageInterface $message
   *   The Migrate message service.
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message) {
    $this->migration = $migration;
    $this->message = $message;
  }

  /**
   * Gets the migration.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration being run.
   */
  public function getMigration() {
    return $this->migration;
  }

  /**
   * Logs a message using the Migrate message service.
   *
   * @param string $message
   *   The message to log.
   * @param string $type
   *   The type of message, for example: status or warning.
   */
  public function logMessage($message, $type = 'status') {
    $this->message->display($message, $type);
  }

}
