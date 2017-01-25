<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\ExceptionJsonSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\ExceptionJsonSubscriber
 * @group EventSubscriber
 */
class ExceptionJsonSubscriberTest extends UnitTestCase {

  /**
   * @covers ::on4xx
   */
  public function testOn4xx() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/test');
    $e = new MethodNotAllowedHttpException(['POST', 'PUT'], 'test message');
    $event = new GetResponseForExceptionEvent($kernel->reveal(), $request, 'GET', $e);
    $subscriber = new ExceptionJsonSubscriber();
    $subscriber->on4xx($event);
    $response = $event->getResponse();

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertEquals('{"message":"test message"}', $response->getContent());
    $this->assertEquals(405, $response->getStatusCode());
    $this->assertEquals('POST, PUT', $response->headers->get('Allow'));
    $this->assertEquals('application/json', $response->headers->get('Content-Type'));
  }

}
