<?php

namespace Drupal\Core\Database\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to statement executions.
 */
class StatementExecutionSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      StatementExecutionEndEvent::class => 'onStatementExecutionEnd',
    ];
  }

  /**
   * Subscribes to a statement execution finished event.
   *
   * Logs the statement query if logging is active.
   *
   * @param \Drupal\Core\Database\Event\StatementExecutionEndEvent $event
   *   The database event.
   */
  public function onStatementExecutionEnd(StatementExecutionEndEvent $event): void {
    $logger = Database::getConnection($event->target, $event->key)->getLogger();
    if ($logger) {
      $logger->logFromEvent($event);
    }
  }

}
