<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\StackMiddleware\ContentLength;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\StackMiddleware\ContentLength
 * @group Middleware
 */
class ContentLengthTest extends UnitTestCase {

  /**
   * @covers ::handle
   * @dataProvider providerTestSetContentLengthHeader
   */
  public function testHandle(false|int $expected_header, Response $response) {
    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/');
    $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, TRUE)->willReturn($response);
    $middleware = new ContentLength($kernel->reveal());
    $response = $middleware->handle($request);
    if ($expected_header === FALSE) {
      $this->assertFalse($response->headers->has('Content-Length'));
      return;
    }
    $this->assertSame((string) $expected_header, $response->headers->get('Content-Length'));
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
