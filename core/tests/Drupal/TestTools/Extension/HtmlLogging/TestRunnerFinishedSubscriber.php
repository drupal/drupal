<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\HtmlLogging;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;

/**
 * Event subscriber notifying end of test runner execution to HTML logging.
 *
 * @internal
 */
final class TestRunnerFinishedSubscriber extends SubscriberBase implements FinishedSubscriber {

  public function notify(Finished $event): void {
    $this->logger()->testRunnerFinished($event);
  }

}
