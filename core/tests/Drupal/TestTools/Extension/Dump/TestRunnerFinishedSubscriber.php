<?php

declare(strict_types=1);

namespace Drupal\TestTools\Extension\Dump;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;

/**
 * Event subscriber notifying end of test runner execution to HTML logging.
 *
 * @internal
 */
final class TestRunnerFinishedSubscriber implements FinishedSubscriber {

  public function __construct(
    private readonly DebugDump $dump,
  ) {
  }

  public function notify(Finished $event): void {
    $this->dump->testRunnerFinished($event);
  }

}
