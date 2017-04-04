<?php

namespace Drupal\Tests\migrate\Unit\Event;

use Drupal\migrate\Event\EventBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate\Event\EventBase
 * @group migrate
 */
class EventBaseTest extends UnitTestCase {

  /**
   * Test getMigration method.
   *
   * @covers ::__construct
   * @covers ::getMigration
   */
  public function testGetMigration() {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface')->reveal();
    $row = $this->prophesize('\Drupal\migrate\Row')->reveal();
    $event = new EventBase($migration, $message_service, $row, [1, 2, 3]);
    $this->assertSame($migration, $event->getMigration());
  }

  /**
   * Test logging a message.
   *
   * @covers ::__construct
   * @covers ::logMessage
   */
  public function testLogMessage() {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface');
    $event = new EventBase($migration, $message_service->reveal());
    // Assert that the intended calls to the services happen.
    $message_service->display('status message', 'status')->shouldBeCalledTimes(1);
    $event->logMessage('status message');
    $message_service->display('warning message', 'warning')->shouldBeCalledTimes(1);
    $event->logMessage('warning message', 'warning');
  }

}
