<?php

declare(strict_types=1);

namespace Drupal\Core\Test;

use PHPUnit\Event\Event;
use PHPUnit\Event\Test\PhpunitErrorTriggered;
use PHPUnit\Event\Test\PhpunitWarningTriggered;
use PHPUnit\Event\TestRunner\WarningTriggered;
use PHPUnit\Event\Tracer\Tracer;

/**
 * Traces events dispatched by PHPUnit during the test discovery.
 *
 * @internal
 */
class PhpUnitTestDiscoveryTracer implements Tracer {

  public function __construct(
    private readonly PHPUnitTestDiscovery $testDiscovery,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function trace(Event $event): void {
    if (in_array(get_class($event), [
      PhpunitErrorTriggered::class,
      PhpunitWarningTriggered::class,
      WarningTriggered::class,
    ])) {
      $this->testDiscovery->addWarning(sprintf('%s: %s', get_class($event), $event->message()));
    }
  }

}
