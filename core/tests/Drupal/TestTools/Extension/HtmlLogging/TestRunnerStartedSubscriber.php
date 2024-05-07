<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\HtmlLogging;

use PHPUnit\Event\TestRunner\Started;
use PHPUnit\Event\TestRunner\StartedSubscriber;

/**
 * Event subscriber notifying beginning of test runner to HTML logging.
 *
 * @internal
 */
final class TestRunnerStartedSubscriber extends SubscriberBase implements StartedSubscriber {

  public function notify(Started $event): void {
    $this->logger()->testRunnerStarted($event);
  }

}
