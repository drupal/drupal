<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StackMiddleware;

use Drupal\Core\StackMiddleware\NegotiationMiddleware;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\Core\StackMiddleware\NegotiationMiddleware
 * @group NegotiationMiddleware
 */
class NegotiationMiddlewareTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $app;

  /**
   * @var \Drupal\Tests\Core\StackMiddleware\StubNegotiationMiddleware
   */
  protected $contentNegotiation;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->app = $this->prophesize(HttpKernelInterface::class);
    $this->contentNegotiation = new StubNegotiationMiddleware($this->app->reveal());
  }

  /**
   * Tests the getContentType() method with AJAX iframe upload.
   *
   * @covers ::getContentType
   */
  public function testAjaxIframeUpload() {
    $request = new Request();
    $request->request->set('ajax_iframe_upload', '1');

    $this->assertSame('iframeupload', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the specifying a format via query parameters gets used.
   *
   * @covers ::getContentType
   */
  public function testFormatViaQueryParameter() {
    $request = new Request();
    $request->query->set('_format', 'bob');

    $this->assertSame('bob', $this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found.
   *
   * @covers ::getContentType
   */
  public function testUnknownContentTypeReturnsNull() {
    $request = new Request();

    $this->assertNull($this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests the getContentType() method when no priority format is found but it's an AJAX request.
   *
   * @covers ::getContentType
   */
  public function testUnknownContentTypeButAjaxRequest() {
    $request = new Request();
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');

    $this->assertNull($this->contentNegotiation->getContentType($request));
  }

  /**
   * Tests that handle() correctly hands off to sub application.
   *
   * @covers ::handle
   */
  public function testHandle() {
    $request = $this->prophesize(Request::class);

    // Default empty format list should not set any formats.
    $request->setFormat()->shouldNotBeCalled();

    // Request format will be set with default format.
    $request->setRequestFormat()->shouldNotBeCalled();

    // Some getContentType calls we don't really care about but have to mock.
    $request_mock = $request->reveal();
    $request_mock->query = new InputBag();
    $request_mock->request = new InputBag();

    // Calling kernel app with default arguments.
    $this->app->handle($request_mock, HttpKernelInterface::MAIN_REQUEST, TRUE)
      ->shouldBeCalled()
      ->willReturn(
        $this->createMock(Response::class)
      );
    $this->contentNegotiation->handle($request_mock);
    // Calling kernel app with specified arguments.
    $this->app->handle($request_mock, HttpKernelInterface::SUB_REQUEST, FALSE)
      ->shouldBeCalled()
      ->willReturn(
        $this->createMock(Response::class)
      );
    $this->contentNegotiation->handle($request_mock, HttpKernelInterface::SUB_REQUEST, FALSE);
  }

  /**
   * @covers ::registerFormat
   */
  public function testSetFormat() {
    $app = $this->createMock(HttpKernelInterface::class);
    $app->expects($this->once())
      ->method('handle')
      ->willReturn($this->createMock(Response::class));

    $content_negotiation = new StubNegotiationMiddleware($app);

    $request = $this->prophesize(Request::class);

    // Default empty format list should not set any formats.
    $request->setFormat('david', 'geeky/david')->shouldBeCalled();

    // Some calls we don't care about.
    $request->setRequestFormat()->shouldNotBeCalled();
    $request_mock = $request->reveal();
    $request_mock->query = new InputBag();
    $request_mock->request = new InputBag();

    // Trigger handle.
    $content_negotiation->registerFormat('david', 'geeky/david');
    $content_negotiation->handle($request_mock);
  }

}

class StubNegotiationMiddleware extends NegotiationMiddleware {

  public function getContentType(Request $request) {
    return parent::getContentType($request);
  }

}
