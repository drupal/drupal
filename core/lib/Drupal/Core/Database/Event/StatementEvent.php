<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Event;

/**
 * Enumeration of the statement related database events.
 */
enum StatementEvent: string {

  case ExecutionStart = StatementExecutionStartEvent::class;
  case ExecutionEnd = StatementExecutionEndEvent::class;
  case ExecutionFailure = StatementExecutionFailureEvent::class;

  /**
   * Returns an array with all statement related events.
   *
   * @return list<class-string<\Drupal\Core\Database\Event\DatabaseEvent>>
   *   An array with all statement related events.
   */
  public static function all(): array {
    return array_map(fn(self $case) => $case->value, self::cases());
  }

}
