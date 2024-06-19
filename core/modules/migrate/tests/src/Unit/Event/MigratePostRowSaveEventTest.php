<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Event;

use Drupal\migrate\Event\MigratePostRowSaveEvent;

/**
 * @coversDefaultClass \Drupal\migrate\Event\MigratePostRowSaveEvent
 * @group migrate
 */
class MigratePostRowSaveEventTest extends EventBaseTest {

  /**
   * Tests getDestinationIdValues method.
   *
   * @covers ::__construct
   * @covers ::getDestinationIdValues
   */
  public function testGetDestinationIdValues(): void {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface')->reveal();
    $row = $this->prophesize('\Drupal\migrate\Row')->reveal();
    $event = new MigratePostRowSaveEvent($migration, $message_service, $row, [1, 2, 3]);
    $this->assertSame([1, 2, 3], $event->getDestinationIdValues());
  }

  /**
   * Tests getRow method.
   *
   * @covers ::__construct
   * @covers ::getRow
   */
  public function testGetRow(): void {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface');
    $row = $this->prophesize('\Drupal\migrate\Row')->reveal();
    $event = new MigratePostRowSaveEvent($migration, $message_service->reveal(), $row, [1, 2, 3]);
    $this->assertSame($row, $event->getRow());
  }

}
