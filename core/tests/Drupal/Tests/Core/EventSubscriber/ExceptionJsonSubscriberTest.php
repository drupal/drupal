<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\EventSubscriber\ExceptionJsonSubscriber;
use Drupal\Core\Http\Exception\CacheableMethodNotAllowedHttpException;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\ExceptionJsonSubscriber
 * @group EventSubscriber
 */
class ExceptionJsonSubscriberTest extends UnitTestCase {

  /**
   * @covers ::on4xx
   * @dataProvider providerTestOn4xx
   */
  public function testOn4xx(HttpExceptionInterface $exception, $expected_response_class) {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/test');
    $event = new GetResponseForExceptionEvent($kernel->reveal(), $request, 'GET', $exception);
    $subscriber = new ExceptionJsonSubscriber();
    $subscriber->on4xx($event);
    $response = $event->getResponse();

    $this->assertInstanceOf($expected_response_class, $response);
    $this->assertEquals('{"message":"test message"}', $response->getContent());
    $this->assertEquals(405, $response->getStatusCode());
    $this->assertEquals('POST, PUT', $response->headers->get('Allow'));
    $this->assertEquals('application/json', $response->headers->get('Content-Type'));
  }

  public function providerTestOn4xx() {
    return [
      'uncacheable exception' => [
        new MethodNotAllowedHttpException(['POST', 'PUT'], 'test message'),
        JsonResponse::class,
      ],
      'cacheable exception' => [
        new CacheableMethodNotAllowedHttpException((new CacheableMetadata())->setCacheContexts(['route']), ['POST', 'PUT'], 'test message'),
        CacheableJsonResponse::class,
      ],
    ];
  }

}
