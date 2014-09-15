<?php

/**
 * @file
 * Contains
 *   \Drupal\Tests\Core\EventSubscriber\CustomPageExceptionHtmlSubscriberTest.
 */

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber
 * @group EventSubscriber
 */
class CustomPageExceptionHtmlSubscriberTest extends UnitTestCase {

  /**
   * The mocked HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $kernel;

  /**
   * The mocked config factory
   *
   * @var  \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

  /**
   * The mocked alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasManager;

  /**
   * The mocked logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The PHP error log settings before the test.
   *
   * @var string
   */
  protected $errorLog;

  /**
   * The tested custom page exception subscriber.
   *
   * @var \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber
   */
  protected $customPageSubscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->configFactory = $this->getConfigFactoryStub(['system.site' => ['page.403' => 'access-denied-page', 'page.404' => 'not-found-page']]);

    $this->aliasManager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    $this->customPageSubscriber = new CustomPageExceptionHtmlSubscriber($this->configFactory, $this->aliasManager, $this->kernel, $this->logger);

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
   * Sets up an alias manager that does nothing.
   */
  protected function setupStubAliasManager() {
    $this->aliasManager->expects($this->any())
      ->method('getPathByAlias')
      ->willReturnArgument(0);
  }

  /**
   * Tests onHandleException with a POST request.
   */
  public function testHandleWithPostRequest() {
    $this->setupStubAliasManager();

    $request = Request::create('/test', 'POST', array('name' => 'druplicon', 'pass' => '12345'));

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new Response($request->getMethod());
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new NotFoundHttpException('foo'));

    $this->customPageSubscriber->onException($event);

    $response = $event->getResponse();
    $result = $response->getContent() . " " . UrlHelper::buildQuery($request->request->all());
    $this->assertEquals('POST name=druplicon&pass=12345', $result);
  }

  /**
   * Tests onHandleException with a GET request.
   */
  public function testHandleWithGetRequest() {
    $this->setupStubAliasManager();

    $request = Request::create('/test', 'GET', array('name' => 'druplicon', 'pass' => '12345'));
    $request->attributes->set('_system_path', 'test');

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new Response($request->getMethod() . ' ' . UrlHelper::buildQuery($request->query->all()));
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new NotFoundHttpException('foo'));
    $this->customPageSubscriber->onException($event);

    $response = $event->getResponse();
    $result = $response->getContent() . " " . UrlHelper::buildQuery($request->request->all());
    $this->assertEquals('GET name=druplicon&pass=12345&destination=test&_exception_statuscode=404 ', $result);
  }

}
