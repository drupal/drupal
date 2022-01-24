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
    $event = new KernelEvent($kernel, $request, $kernel::MAIN_REQUEST);

    $this->expectDeprecation('Since symfony/http-kernel 5.3: "Symfony\Component\HttpKernel\Event\KernelEvent::isMasterRequest()" is deprecated, use "isMainRequest()" instead.');
    $this->assertTrue($event->isMasterRequest());
  }

}
