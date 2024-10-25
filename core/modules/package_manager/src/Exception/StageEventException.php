<?php

declare(strict_types=1);

namespace Drupal\package_manager\Exception;

use Drupal\package_manager\Event\PreOperationStageEvent;
use Drupal\package_manager\Event\StageEvent;

/**
 * Exception thrown if an error related to an event occurs.
 *
 * This exception is thrown when an error strictly associated with an event
 * occurs. This is also what makes it different from StageException.
 *
 * Should not be thrown by external code.
 */
class StageEventException extends StageException {

  /**
   * Constructs a StageEventException object.
   *
   * @param \Drupal\package_manager\Event\StageEvent $event
   *   The stage event during which this exception is thrown.
   * @param string|null $message
   *   (optional) The exception message. Defaults to a plain text representation
   *   of the validation results.
   * @param mixed ...$arguments
   *   Additional arguments to pass to the parent constructor.
   */
  public function __construct(public readonly StageEvent $event, ?string $message = NULL, ...$arguments) {
    parent::__construct($event->stage, $message ?: $this->getResultsAsText(), ...$arguments);
  }

  /**
   * Formats the validation results, if any, as plain text.
   *
   * @return string
   *   The results, formatted as plain text.
   */
  protected function getResultsAsText(): string {
    $text = '';
    if ($this->event instanceof PreOperationStageEvent) {
      foreach ($this->event->getResults() as $result) {
        $messages = $result->messages;
        $summary = $result->summary;
        if ($summary) {
          array_unshift($messages, $summary);
        }
        $text .= implode("\n", $messages) . "\n";
      }
    }
    return $text;
  }

}
