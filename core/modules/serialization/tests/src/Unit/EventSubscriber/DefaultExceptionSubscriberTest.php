<?php

namespace Drupal\Tests\serialization\Unit\EventSubscriber;

use Drupal\serialization\Encoder\JsonEncoder;
use Drupal\serialization\EventSubscriber\DefaultExceptionSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @coversDefaultClass \Drupal\serialization\EventSubscriber\DefaultExceptionSubscriber
 * @group serialization
 */
class DefaultExceptionSubscriberTest extends UnitTestCase {

  /**
   * @covers ::on4xx
   */
  public function testOn4xx() {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/test');
    $request->setRequestFormat('json');

    $e = new MethodNotAllowedHttpException(['POST', 'PUT'], 'test message');
    $event = new ExceptionEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST, $e);
    $subscriber = new DefaultExceptionSubscriber(new Serializer([], [new JsonEncoder()]), []);
    $subscriber->on4xx($event);
    $response = $event->getResponse();

    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals('{"message":"test message"}', $response->getContent());
    $this->assertEquals(405, $response->getStatusCode());
    $this->assertEquals('POST, PUT', $response->headers->get('Allow'));
    $this->assertEquals('application/json', $response->headers->get('Content-Type'));
  }

}
