<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\EventSubscriber\ExceptionListenerTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\EventSubscriber\ExceptionListener;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\ExceptionListener
 * @group EventSubscriber
 */
class ExceptionListenerTest extends UnitTestCase {

  /**
   * The tested exception listener.
   *
   * @var \Drupal\Core\EventSubscriber\ExceptionListener
   */
  protected $exceptionListener;

  /**
   * The mocked HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $kernel;

  /**
   * The PHP error log settings before the test.
   *
   * @var string
   */
  protected $errorLog;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->exceptionListener = new ExceptionListener('example');
    $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');

    // You can't create an exception in PHP without throwing it. Store the
    // current error_log, and disable it temporarily.
    $this->errorLog = ini_set('error_log', file_exists('/dev/null') ? '/dev/null' : 'nul');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    ini_set('error_log', $this->errorLog);
  }

  /**
   * Tests onHandleException with a POST request.
   */
  public function testHandleWithPostRequest() {
    $request = Request::create('/test', 'POST', array('name' => 'druplicon', 'pass' => '12345'));

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new Response($request->getMethod());
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new \Exception('foo'));

    $this->exceptionListener->onKernelException($event);

    $response = $event->getResponse();
    $this->assertEquals('POST name=druplicon&pass=12345', $response->getContent() . " " . UrlHelper::buildQuery($request->request->all()));
  }

  /**
   * Tests onHandleException with a GET request.
   */
  public function testHandleWithGetRequest() {
    $request = Request::create('/test', 'GET', array('name' => 'druplicon', 'pass' => '12345'));

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new Response($request->getMethod() . ' ' . UrlHelper::buildQuery($request->query->all()));
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new \Exception('foo'));
    $this->exceptionListener->onKernelException($event);

    $response = $event->getResponse();
    $this->assertEquals('GET name=druplicon&pass=12345 ', $response->getContent() . " " . UrlHelper::buildQuery($request->request->all()));
  }

}
