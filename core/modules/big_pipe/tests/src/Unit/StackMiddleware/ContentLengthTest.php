<?php

declare(strict_types=1);

namespace Drupal\Tests\big_pipe\Unit\StackMiddleware;

use Drupal\big_pipe\Render\BigPipeResponse;
use Drupal\big_pipe\StackMiddleware\ContentLength;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Defines a test for ContentLength middleware.
 */
#[CoversClass(ContentLength::class)]
#[Group('big_pipe')]
final class ContentLengthTest extends UnitTestCase {

  /**
   * Tests handle.
   *
   * @legacy-covers ::handle
   */
  #[DataProvider('providerTestSetContentLengthHeader')]
  public function testHandle(false|int $expected_header, Response $response): void {
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

  /**
   * Provides data for testHandle().
   */
  public static function providerTestSetContentLengthHeader() {
    $response = new Response('Test content', 200);
    $response->headers->set('Content-Length', (string) strlen('Test content'));
    return [
      '200 ok' => [
        12,
        $response,
      ],
      'Big pipe' => [
        FALSE,
        new BigPipeResponse(new HtmlResponse('Test content')),
      ],
    ];
  }

}
