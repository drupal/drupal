<?php

namespace Drupal\Tests\Core\Queue;

use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Tests\UnitTestCase;

/**
 * Tests queue exceptions.
 *
 * @group Queue
 */
class QueueExceptionsTest extends UnitTestCase {

  /**
   * Tests that the `DelayedRequeueException` calls parent constructor.
   */
  public function testDelayedRequeueExceptionCallsParentConstructor(): void {
    $without_previous = new DelayedRequeueException(50, 'Delay the processing.');
    static::assertSame(50, $without_previous->getDelay());
    static::assertSame('Delay the processing.', $without_previous->getMessage());
    static::assertSame(0, $without_previous->getCode());
    static::assertNull($without_previous->getPrevious());

    $with_previous = new DelayedRequeueException(100, 'Increase the delay.', 200, $without_previous);
    static::assertSame(100, $with_previous->getDelay());
    static::assertSame('Increase the delay.', $with_previous->getMessage());
    static::assertSame(200, $with_previous->getCode());
    static::assertSame($without_previous, $with_previous->getPrevious());
  }

}
