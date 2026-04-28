<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\CsrfExceptionSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\EventSubscriber\CsrfExceptionSubscriber.
 */
#[CoversClass(CsrfExceptionSubscriber::class)]
#[Group('EventSubscriber')]
class CsrfExceptionSubscriberTest extends UnitTestCase {

  /**
   * Tests on403() with no matched route.
   */
  public function testOn403WithNullRouteDoesNothing(): void {
    $subscriber = new CsrfExceptionSubscriber();

    $request = new Request();
    $kernel = $this->createStub(HttpKernelInterface::class);
    $exception = new AccessDeniedHttpException();

    $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);

    $subscriber->on403($event);

    $this->assertNull($event->getResponse());
  }

}
