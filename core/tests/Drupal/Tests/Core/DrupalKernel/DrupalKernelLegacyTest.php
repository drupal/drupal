<?php

namespace Drupal\Tests\Core\DrupalKernel;

use Drupal\Core\DrupalKernel;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;

/**
 * @coversDefaultClass \Drupal\Core\DrupalKernel
 * @group legacy
 */
class DrupalKernelLegacyTest extends UnitTestCase {

  /**
   * Tests deprecation message in overridden KernelEvent.
   *
   * @covers ::isMasterRequest
   */
  public function testKernelEventDeprecation() {
    $kernel = $this->createMock(DrupalKernel::class);
    $request = $this->createMock(Request::class);
    $event = new KernelEvent($kernel, $request, $kernel::MASTER_REQUEST);

    $this->expectDeprecation('Symfony\Component\HttpKernel\Event\KernelEvent::isMasterRequest() is deprecated, use isMainRequest()');
    $this->assertTrue($event->isMasterRequest());
  }

}
