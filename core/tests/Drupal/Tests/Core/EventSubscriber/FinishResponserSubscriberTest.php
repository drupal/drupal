<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\EventSubscriber\FinishResponseSubscriber;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\FinishResponseSubscriber
 * @group EventSubscriber
 */
class FinishResponserSubscriberTest extends UnitTestCase {

  /**
   * @covers ::setContentLengthHeader
   * @dataProvider providerTestSetContentLengthHeader
   */
  public function testSetContentLengthHeader(false|int $expected_header, Response $response) {
    $event_subscriber = new FinishResponseSubscriber(
      $this->prophesize(LanguageManagerInterface::class)->reveal(),
      $this->getConfigFactoryStub(),
      $this->prophesize(RequestPolicyInterface::class)->reveal(),
      $this->prophesize(ResponsePolicyInterface::class)->reveal(),
      $this->prophesize(CacheContextsManager::class)->reveal()
    );

    $event = new ResponseEvent(
      $this->prophesize(HttpKernelInterface::class)->reveal(),
      $this->prophesize(Request::class)->reveal(),
      HttpKernelInterface::MAIN_REQUEST,
      $response
    );

    $event_subscriber->setContentLengthHeader($event);
    if ($expected_header === FALSE) {
      $this->assertFalse($event->getResponse()->headers->has('Content-Length'));
    }
    else {
      $this->assertSame((string) $expected_header, $event->getResponse()->headers->get('Content-Length'));
    }
  }

  public function providerTestSetContentLengthHeader() {
    return [
      'Informational' => [
        FALSE,
        new Response('', 101),
      ],
      '200 ok' => [
        12,
        new Response('Test content', 200),
      ],
      '204' => [
        FALSE,
        new Response('Test content', 204),
      ],
      '304' => [
        FALSE,
        new Response('Test content', 304),
      ],
      'Client error' => [
        13,
        new Response('Access denied', 403),
      ],
      'Server error' => [
        FALSE,
        new Response('Test content', 500),
      ],
      '200 with transfer-encoding' => [
        FALSE,
        new Response('Test content', 200, ['Transfer-Encoding' => 'Chunked']),
      ],
      '200 with FalseContentResponse' => [
        FALSE,
        new FalseContentResponse('Test content', 200),
      ],
      '200 with StreamedResponse' => [
        FALSE,
        new StreamedResponse(status: 200),
      ],

    ];
  }

}

/**
 * Response that returns FALSE from ::getContent().
 */
class FalseContentResponse extends Response {

  /**
   * {@inheritdoc}
   */
  public function getContent(): string|false {
    return FALSE;
  }

}
