<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber;
use Drupal\Core\Render\HtmlResponse;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RequestContext;

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
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $configFactory;

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
   * @var \Drupal\Core\EventSubscriber\CustomPageExceptionHtmlSubscriber|\Drupal\Tests\Core\EventSubscriber\TestCustomPageExceptionHtmlSubscriber
   */
  protected $customPageSubscriber;

  /**
   * The mocked redirect.destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $redirectDestination;

  /**
   * The mocked access unaware router.
   * @var \Symfony\Component\Routing\Matcher\UrlMatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessUnawareRouter;

  /**
   * The access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->configFactory = $this->getConfigFactoryStub(['system.site' => ['page.403' => '/access-denied-page', 'page.404' => '/not-found-page']]);

    $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    $this->redirectDestination = $this->getMock('\Drupal\Core\Routing\RedirectDestinationInterface');
    $this->redirectDestination->expects($this->any())
      ->method('getAsArray')
      ->willReturn(['destination' => 'test']);
    $this->accessUnawareRouter = $this->getMock('Symfony\Component\Routing\Matcher\UrlMatcherInterface');
    $this->accessUnawareRouter->expects($this->any())
      ->method('match')
      ->willReturn([
        '_controller' => 'mocked',
      ]);
    $this->accessManager = $this->getMock('Drupal\Core\Access\AccessManagerInterface');
    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->willReturn(AccessResult::allowed()->addCacheTags(['foo', 'bar']));

    $this->customPageSubscriber = new CustomPageExceptionHtmlSubscriber($this->configFactory, $this->kernel, $this->logger, $this->redirectDestination, $this->accessUnawareRouter, $this->accessManager);

    $path_validator = $this->getMock('Drupal\Core\Path\PathValidatorInterface');
    $path_validator->expects($this->any())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->willReturn(Url::fromRoute('foo', ['foo' => 'bar']));
    $container = new ContainerBuilder();
    $container->set('path.validator', $path_validator);
    \Drupal::setContainer($container);

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

    $request_context = new RequestContext();
    $request_context->fromRequest($request);
    $this->accessUnawareRouter->expects($this->any())
      ->method('getContext')
      ->willReturn($request_context);

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new HtmlResponse($request->getMethod());
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new NotFoundHttpException('foo'));

    $this->customPageSubscriber->onException($event);

    $response = $event->getResponse();
    $result = $response->getContent() . " " . UrlHelper::buildQuery($request->request->all());
    $this->assertEquals('POST name=druplicon&pass=12345', $result);
    $this->assertEquals(AccessResult::allowed()->addCacheTags(['foo', 'bar']), $request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT));
  }

  /**
   * Tests onHandleException with a GET request.
   */
  public function testHandleWithGetRequest() {
    $request = Request::create('/test', 'GET', array('name' => 'druplicon', 'pass' => '12345'));
    $request->attributes->set(AccessAwareRouterInterface::ACCESS_RESULT, AccessResult::forbidden()->addCacheTags(['druplicon']));

    $request_context = new RequestContext();
    $request_context->fromRequest($request);
    $this->accessUnawareRouter->expects($this->any())
      ->method('getContext')
      ->willReturn($request_context);

    $this->kernel->expects($this->once())->method('handle')->will($this->returnCallback(function (Request $request) {
      return new Response($request->getMethod() . ' ' . UrlHelper::buildQuery($request->query->all()));
    }));

    $event = new GetResponseForExceptionEvent($this->kernel, $request, 'foo', new NotFoundHttpException('foo'));
    $this->customPageSubscriber->onException($event);

    $response = $event->getResponse();
    $result = $response->getContent() . " " . UrlHelper::buildQuery($request->request->all());
    $this->assertEquals('GET name=druplicon&pass=12345&destination=test&_exception_statuscode=404 ', $result);
    $this->assertEquals(AccessResult::forbidden()->addCacheTags(['druplicon', 'foo', 'bar']), $request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT));
  }

}
