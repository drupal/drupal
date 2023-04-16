<?php

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Event\DatabaseEvent;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Drupal\Core\Database\Exception\EventException;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Database\Connection
 *
 * @group Database
 */
class DatabaseEventsTest extends UnitTestCase {

  /**
   * A database connection.
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = new StubConnection($this->createMock(StubPDO::class), []);
  }

  /**
   * @covers ::isEventEnabled
   * @covers ::enableEvents
   * @covers ::disableEvents
   */
  public function testEventEnablingAndDisabling(): void {
    $this->connection->enableEvents([
      StatementExecutionStartEvent::class,
      StatementExecutionEndEvent::class,
    ]);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->connection->disableEvents([
      StatementExecutionEndEvent::class,
    ]);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
  }

  /**
   * @covers ::enableEvents
   */
  public function testEnableInvalidEvent(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('Event class foo does not exist');
    $this->connection->enableEvents(['foo']);
  }

  /**
   * @covers ::disableEvents
   */
  public function testDisableInvalidEvent(): void {
    $this->expectException(\AssertionError::class);
    $this->expectExceptionMessage('Event class bar does not exist');
    $this->connection->disableEvents(['bar']);
  }

  /**
   * @covers ::dispatchEvent
   */
  public function testEventDispatchingWhenNoContainerAvailable(): void {
    $this->expectException(EventException::class);
    $this->expectExceptionMessage('The event dispatcher service is not available. Database API events can only be fired if the container is initialized');
    $this->connection->dispatchEvent($this->createMock(DatabaseEvent::class));
  }

}
