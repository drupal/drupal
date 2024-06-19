<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Routing;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the exception handling for various cases.
 *
 * @group Routing
 */
class ExceptionHandlingTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'router_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('date_format');
  }

  /**
   * Tests on a route with a non-supported HTTP method.
   */
  public function test405(): void {
    $request = Request::create('/router_test/test15', 'PATCH');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
  }

  /**
   * Tests a route with a non-supported _format parameter.
   */
  public function test406(): void {
    $request = Request::create('/router_test/test2?_format=non_existent_format');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $response->getStatusCode());
  }

  /**
   * Tests the exception handling for json and 403 status code.
   */
  public function testJson403(): void {
    $request = Request::create('/router_test/test15');
    $request->query->set('_format', 'json');
    $request->setRequestFormat('json');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals('application/json', $response->headers->get('Content-type'));
    $this->assertEquals('{"message":""}', $response->getContent());
    $this->assertInstanceOf(CacheableJsonResponse::class, $response);
  }

  /**
   * Tests the exception handling for json and 404 status code.
   */
  public function testJson404(): void {
    $request = Request::create('/not-found');
    $request->query->set('_format', 'json');
    $request->setRequestFormat('json');

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $this->assertEquals('application/json', $response->headers->get('Content-type'));
    $this->assertEquals('{"message":"No route found for \\u0022GET http:\/\/localhost\\/not-found\\u0022"}', $response->getContent());
  }

  /**
   * Tests the exception handling for HTML and 403 status code.
   */
  public function testHtml403(): void {
    $request = Request::create('/router_test/test15');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);

    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-type'));
  }

  /**
   * Tests the exception handling for HTML and 404 status code.
   */
  public function testHtml404(): void {
    $request = Request::create('/not-found');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);

    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-type'));
  }

  /**
   * Tests that the exception response is executed in the original context.
   */
  public function testExceptionResponseGeneratedForOriginalRequest(): void {
    // Test with 404 path pointing to a route that uses '_controller'.
    $response = $this->doTest404Route('/router_test/test25');
    $this->assertStringContainsString('/not-found', $response->getContent());

    // Test with 404 path pointing to a route that uses '_form'.
    $response = $this->doTest404Route('/router_test/test26');
    $this->assertStringContainsString('<form class="system-logging-settings"', $response->getContent());

    // Test with 404 path pointing to a route that uses '_entity_form'.
    $response = $this->doTest404Route('/router_test/test27');
    $this->assertStringContainsString('<form class="date-format-add-form date-format-form"', $response->getContent());
  }

  /**
   * Sets the given path to use as the 404 page and triggers a 404.
   *
   * @param string $path
   *   The path to test.
   *
   * @return \Drupal\Core\Render\HtmlResponse
   *
   * @see \Drupal\system\Tests\Routing\ExceptionHandlingTest::testExceptionResponseGeneratedForOriginalRequest()
   */
  protected function doTest404Route($path) {
    $this->config('system.site')->set('page.404', $path)->save();

    $request = Request::create('/not-found');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    return $kernel->handle($request)->prepare($request);
  }

  /**
   * Tests if exception backtraces are properly escaped when output to HTML.
   */
  public function testBacktraceEscaping(): void {
    // Enable verbose error logging.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    $request = Request::create('/router_test/test17');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);
    $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-type'));

    // Test both that the backtrace is properly escaped, and that the unescaped
    // string is not output at all.
    $this->assertStringContainsString(Html::escape('<script>alert(\'xss\')</script>'), $response->getContent());
    $this->assertStringNotContainsString('<script>alert(\'xss\')</script>', $response->getContent());
  }

  /**
   * Tests exception message escaping.
   */
  public function testExceptionEscaping(): void {
    // Enable verbose error logging.
    $this->config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    // Using \Drupal\Component\Render\FormattableMarkup.
    $request = Request::create('/router_test/test24');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);
    $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    $this->assertEquals('text/html; charset=UTF-8', $response->headers->get('Content-type'));

    // Test message is properly escaped, and that the unescaped string is not
    // output at all.
    $this->setRawContent($response->getContent());
    $this->assertRaw(Html::escape('Escaped content: <p> <br> <h3>'));
    $this->assertNoRaw('<p> <br> <h3>');

    $string = '<script>alert(123);</script>';
    $request = Request::create('/router_test/test2?_format=json' . urlencode($string), 'GET');

    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);
    // As the Content-type is text/plain the fact that the raw string is
    // contained in the output would not matter, but because it is output by the
    // final exception subscriber, it is printed as partial HTML, and hence
    // escaped.
    $this->assertEquals('text/plain; charset=UTF-8', $response->headers->get('Content-type'));
    // cspell:ignore jsonalert
    $this->assertStringStartsWith('Not acceptable format: jsonalert(123);', $response->getContent());
  }

}
