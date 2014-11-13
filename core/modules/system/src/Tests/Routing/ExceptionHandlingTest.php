<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Routing\ExceptionHandlingTest.
 */

namespace Drupal\system\Tests\Routing;

use Drupal\Component\Utility\String;
use Drupal\simpletest\KernelTestBase;
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
  public static $modules = ['system', 'router_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests the exception handling for json and 403 status code.
   */
  public function testJson403() {
    $request = Request::create('/router_test/test15');
    $request->headers->set('Accept', 'application/json');
    $request->setFormat('json', ['application/json']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEqual($response->getStatusCode(), Response::HTTP_FORBIDDEN);
    $this->assertEqual($response->headers->get('Content-type'), 'application/json');
    $this->assertEqual('{}', $response->getContent());
  }

  /**
   * Tests the exception handling for json and 404 status code.
   */
  public function testJson404() {
    $request = Request::create('/not-found');
    $request->headers->set('Accept', 'application/json');
    $request->setFormat('json', ['application/json']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request);

    $this->assertEqual($response->getStatusCode(), Response::HTTP_NOT_FOUND);
    $this->assertEqual($response->headers->get('Content-type'), 'application/json');
    $this->assertEqual('{}', $response->getContent());
  }

  /**
   * Tests the exception handling for HTML and 403 status code.
   */
  public function testHtml403() {
    $request = Request::create('/router_test/test15');
    $request->headers->set('Accept', 'text/html');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);

    $this->assertEqual($response->getStatusCode(), Response::HTTP_FORBIDDEN);
    $this->assertEqual($response->headers->get('Content-type'), 'text/html; charset=UTF-8');
  }

  /**
   * Tests the exception handling for HTML and 404 status code.
   */
  public function testHtml404() {
    $request = Request::create('/not-found');
    $request->headers->set('Accept', 'text/html');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);

    $this->assertEqual($response->getStatusCode(), Response::HTTP_NOT_FOUND);
    $this->assertEqual($response->headers->get('Content-type'), 'text/html; charset=UTF-8');
  }

  /**
   * Tests if exception backtraces are properly escaped when output to HTML.
   */
  public function testBacktraceEscaping() {
    // Enable verbose error logging.
    \Drupal::config('system.logging')->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    $request = Request::create('/router_test/test17');
    $request->headers->set('Accept', 'text/html');
    $request->setFormat('html', ['text/html']);

    /** @var \Symfony\Component\HttpKernel\HttpKernelInterface $kernel */
    $kernel = \Drupal::getContainer()->get('http_kernel');
    $response = $kernel->handle($request)->prepare($request);
    $this->assertEqual($response->getStatusCode(), Response::HTTP_INTERNAL_SERVER_ERROR);
    $this->assertEqual($response->headers->get('Content-type'), 'text/html; charset=UTF-8');

    // Test both that the backtrace is properly escaped, and that the unescaped
    // string is not output at all.
    $this->assertTrue(strpos($response->getContent(), String::checkPlain('<script>alert(\'xss\')</script>')) !== FALSE);
    $this->assertTrue(strpos($response->getContent(), '<script>alert(\'xss\')</script>') === FALSE);
  }

}
