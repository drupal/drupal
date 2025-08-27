<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Unit\Event;

use Drupal\migrate\Event\MigratePostRowSaveEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\migrate\Event\MigratePostRowSaveEvent.
 */
#[CoversClass(MigratePostRowSaveEvent::class)]
#[Group('migrate')]
class MigratePostRowSaveEventTest extends EventBaseTest {

  /**
   * Tests getDestinationIdValues method.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getDestinationIdValues
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
   * @legacy-covers ::__construct
   * @legacy-covers ::getRow
   */
  public function testGetRow(): void {
    $migration = $this->prophesize('\Drupal\migrate\Plugin\MigrationInterface')->reveal();
    $message_service = $this->prophesize('\Drupal\migrate\MigrateMessageInterface');
    $row = $this->prophesize('\Drupal\migrate\Row')->reveal();
    $event = new MigratePostRowSaveEvent($migration, $message_service->reveal(), $row, [1, 2, 3]);
    $this->assertSame($row, $event->getRow());
  }

}
