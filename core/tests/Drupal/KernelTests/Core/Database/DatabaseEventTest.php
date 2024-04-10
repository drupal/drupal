<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Event\StatementEvent;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Drupal\database_test\EventSubscriber\DatabaseEventSubscriber;

/**
 * Tests the database API events.
 *
 * @group Database
 */
class DatabaseEventTest extends DatabaseTestBase {

  /**
   * Tests statement execution events.
   */
  public function testStatementExecutionEvents(): void {
    $subscriber = $this->container->get(DatabaseEventSubscriber::class);

    // At first, no events have occurred, and events are not enabled.
    $this->assertSame(0, $subscriber->countStatementStarts);
    $this->assertSame(0, $subscriber->countStatementEnds);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));

    // Execute a query, still no events captured.
    $this->connection->query('SELECT * FROM {test}');
    $this->assertSame(0, $subscriber->countStatementStarts);
    $this->assertSame(0, $subscriber->countStatementEnds);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));

    // Enable the statement execution start event and execute a query, start
    // event captured but no end one.
    $this->connection->enableEvents([StatementExecutionStartEvent::class]);
    $this->connection->query('SELECT * FROM {test}');
    $this->assertSame(1, $subscriber->countStatementStarts);
    $this->assertSame(0, $subscriber->countStatementEnds);
    $this->assertCount(1, $subscriber->statementIdsInExecution);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    // Reset the statements in execution map to cleanup for following tests.
    $subscriber->statementIdsInExecution = [];

    // Enable the statement execution end event and execute a query, both
    // events captured.
    $this->connection->enableEvents([StatementExecutionEndEvent::class]);
    $this->connection->query('SELECT * FROM {test}');
    $this->assertSame(2, $subscriber->countStatementStarts);
    $this->assertSame(1, $subscriber->countStatementEnds);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionEndEvent::class));

    // Enable the statement execution failure event and execute a failing
    // query.
    $this->connection->enableEvents([StatementExecutionFailureEvent::class]);
    try {
      $this->connection->query('bananas on the palm tree');
      $this->fail('An exception was expected, but was not thrown');
    }
    catch (\Exception $e) {
      // Expected, keep going.
    }
    $this->assertSame(3, $subscriber->countStatementStarts);
    $this->assertSame(1, $subscriber->countStatementEnds);
    $this->assertSame(1, $subscriber->countStatementFailures);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionFailureEvent::class));

    // Disable all events, no more events captured.
    $this->connection->disableEvents(StatementEvent::all());
    $this->connection->query('SELECT * FROM {test}');
    $this->assertSame(3, $subscriber->countStatementStarts);
    $this->assertSame(1, $subscriber->countStatementEnds);
    $this->assertSame(1, $subscriber->countStatementFailures);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionFailureEvent::class));

    // Enable the statement execution end only, no events captured since the
    // start event is required before the end one can be fired.
    $this->connection->enableEvents([StatementExecutionEndEvent::class]);
    $this->connection->query('SELECT * FROM {test}');
    $this->assertSame(3, $subscriber->countStatementStarts);
    $this->assertSame(1, $subscriber->countStatementEnds);
    $this->assertEmpty($subscriber->statementIdsInExecution);
    $this->assertFalse($this->connection->isEventEnabled(StatementExecutionStartEvent::class));
    $this->assertTrue($this->connection->isEventEnabled(StatementExecutionEndEvent::class));
  }

}
