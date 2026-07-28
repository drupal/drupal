<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\StackMiddleware\NegotiationMiddleware;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests Drupal\Core\StackMiddleware\NegotiationMiddleware.
 */
#[CoversClass(NegotiationMiddleware::class)]
#[Group('NegotiationMiddleware')]
class NegotiationMiddlewareTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * @var \Drupal\Tests\Core\StackMiddleware\StubNegotiationMiddleware
   */
  protected $contentNegotiation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpKernel = $this->prophesize(HttpKernelInterface::class);
    $this->contentNegotiation = new StubNegotiationMiddleware($this->httpKernel->reveal());
  }

  /**
   * Tests the getContentType() method with AJAX iframe upload.
   *
   * @legacy-covers ::getContentType
   */
  public function testAjaxIframeUpload(): void {
    $request = new Request();
    $request->request->set('ajax_iframe_upload', '1');

    $this->assertSame('iframeupload', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the specifying a format via query parameters gets used.
   *
   * @legacy-covers ::getContentType
   */
  public function testFormatViaQueryParameter(): void {
    $request = new Request();
    $request->query->set('_format', 'bob');

    $this->assertSame('bob', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found.
   *
   * @legacy-covers ::getContentType
   */
  public function testUnknownContentTypeReturnsNull(): void {
    $request = new Request();

    $this->assertNull($this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found but it's an AJAX request.
   *
   * @legacy-covers ::getContentType
   */
  public function testUnknownContentTypeButAjaxRequest(): void {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $this->assertNull($this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests that handle() correctly hands off to sub application.
   */
  public function testHandle(): void {
    $request = new Request();

    // Calling kernel app with default arguments.
    $this->httpKernel->handle($request, HttpKernelInterface::MAIN_REQUEST, TRUE)
      ->shouldBeCalled()
      ->willReturn(
        $this->createStub(Response::class)
      );
    $this->contentNegotiation->handle($request);

    // No format was registered and none was requested, so the request format
    // should not have been set.
    $this->assertNull($request->getRequestFormat(NULL));

    // Calling kernel app with specified arguments.
    $this->httpKernel->handle($request, HttpKernelInterface::SUB_REQUEST, FALSE)
      ->shouldBeCalled()
      ->willReturn(
        $this->createStub(Response::class)
      );
    $this->contentNegotiation->handle($request, HttpKernelInterface::SUB_REQUEST, FALSE);
  }

  /**
   * Tests set format.
   *
   * @legacy-covers ::registerFormat
   */
  public function testSetFormat(): void {
    $httpKernel = $this->createMock(HttpKernelInterface::class);
    $httpKernel->expects($this->once())
      ->method('handle')
      ->willReturn($this->createStub(Response::class));

    $content_negotiation = new StubNegotiationMiddleware($httpKernel);

    $request = new Request();

    // Trigger handle.
    $content_negotiation->registerFormat('david', 'geeky/david');
    $content_negotiation->handle($request);

    $this->assertSame('geeky/david', $request->getMimeType('david'));
  }

}

/**
 * Stub class for testing NegotiationMiddleware.
 */
class StubNegotiationMiddleware extends NegotiationMiddleware {

  public function getContentType(Request $request) {
    return parent::getContentType($request);
  }

}
