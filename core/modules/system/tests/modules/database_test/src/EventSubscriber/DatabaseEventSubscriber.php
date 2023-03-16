<?php

namespace Drupal\database_test\EventSubscriber;

use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionStartEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Responds to database API events.
 */
class DatabaseEventSubscriber implements EventSubscriberInterface {

  /**
   * A counter of started statement executions.
   */
  public int $countStatementStarts = 0;

  /**
   * A counter of finished statement executions.
   */
  public int $countStatementEnds = 0;

  /**
   * A map of statements being executed.
   */
  public array $statementIdsInExecution = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatementExecutionStartEvent::class => 'onStatementExecutionStart',
      StatementExecutionEndEvent::class => 'onStatementExecutionEnd',
    ];
  }

  /**
   * Subscribes to a statement execution started event.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionStartEvent $event
   *   The database event.
   */
  public function onStatementExecutionStart(StatementExecutionStartEvent $event): void {
    $this->statementIdsInExecution[$event->statementObjectId] = TRUE;
    $this->countStatementStarts++;
  }

  /**
   * Subscribes to a statement execution finished event.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionEndEvent $event
   *   The database event.
   */
  public function onStatementExecutionEnd(StatementExecutionEndEvent $event): void {
    unset($this->statementIdsInExecution[$event->statementObjectId]);
    $this->countStatementEnds++;
  }

}
