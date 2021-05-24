<?php

namespace Drupal\Tests\migrate\Unit\Event;

use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Event\MigrateImportEvent
 * @group migrate
 */
class MigrateImportEventTest extends UnitTestCase {

  /**
   * Tests getMigration method.
   *
   * @covers ::__construct
   * @covers ::getMigration
   */
  public function testGetMigration() {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface')->reveal();
    $event = new MigrateImportEvent($migration, $message_service);
    $this->assertSame($migration, $event->getMigration());
  }

  /**
   * Tests logging a message.
   *
   * @covers ::__construct
   * @covers ::logMessage
   */
  public function testLogMessage() {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface');
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface');
    $event = new MigrateImportEvent($migration->reveal(), $message_service->reveal());
    // Assert that the intended calls to the services happen.
    $message_service->display('status message', 'status')->shouldBeCalledTimes(1);
    $event->logMessage('status message');
    $message_service->display('warning message', 'warning')->shouldBeCalledTimes(1);
    $event->logMessage('warning message', 'warning');
  }

}
