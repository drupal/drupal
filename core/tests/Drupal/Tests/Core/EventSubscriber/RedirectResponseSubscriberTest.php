<?php

namespace Drupal\Tests\Core\EventSubscriber;

use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\Core\EventSubscriber\RedirectResponseSubscriber
 * @group EventSubscriber
 */
class RedirectResponseSubscriberTest extends UnitTestCase {

  /**
   * The mocked request context.
   *
   * @var \Drupal\Core\Routing\RequestContext|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestContext;

  /**
   * The mocked request context.
   *
   * @var \Drupal\Core\Utility\UnroutedUrlAssemblerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlAssembler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->requestContext = $this->getMockBuilder('Drupal\Core\Routing\RequestContext')
      ->disableOriginalConstructor()
      ->getMock();
    $this->requestContext->expects($this->any())
      ->method('getCompleteBaseUrl')
      ->willReturn('http://example.com/drupal');

    $this->urlAssembler = $this->getMock(UnroutedUrlAssemblerInterface::class);
    $this->urlAssembler
      ->expects($this->any())
      ->method('assemble')
      ->willReturnMap([
        ['base:test', ['query' => [], 'fragment' => '', 'absolute' => TRUE], FALSE, 'http://example.com/drupal/test'],
        ['base:example.com', ['query' => [], 'fragment' => '', 'absolute' => TRUE], FALSE, 'http://example.com/drupal/example.com'],
        ['base:example:com', ['query' => [], 'fragment' => '', 'absolute' => TRUE], FALSE, 'http://example.com/drupal/example:com'],
        ['base:javascript:alert(0)', ['query' => [], 'fragment' => '', 'absolute' => TRUE], FALSE, 'http://example.com/drupal/javascript:alert(0)'],
      ]);

    $container = new Container();
    $container->set('router.request_context', $this->requestContext);
    \Drupal::setContainer($container);
  }

  /**
   * Test destination detection and redirection.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object with destination query set.
   * @param string|bool $expected
   *   The expected target URL or FALSE.
   *
   * @covers ::checkRedirectUrl
   * @dataProvider providerTestDestinationRedirect
   */
  public function testDestinationRedirect(Request $request, $expected) {
    $dispatcher = new EventDispatcher();
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $response = new RedirectResponse('http://example.com/drupal');
    $request->headers->set('HOST', 'example.com');

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $dispatcher->addListener(KernelEvents::RESPONSE, [$listener, 'checkRedirectUrl']);
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    $target_url = $event->getResponse()->getTargetUrl();
    if ($expected) {
      $this->assertEquals($expected, $target_url);
    }
    else {
      $this->assertEquals('http://example.com/drupal', $target_url);
    }
  }

  /**
   * Data provider for testDestinationRedirect().
   *
   * @see \Drupal\Tests\Core\EventSubscriber\RedirectResponseSubscriberTest::testDestinationRedirect()
   */
  public static function providerTestDestinationRedirect() {
    return [
      [new Request(), FALSE],
      [new Request(['destination' => 'test']), 'http://example.com/drupal/test'],
      [new Request(['destination' => '/drupal/test']), 'http://example.com/drupal/test'],
      [new Request(['destination' => 'example.com']), 'http://example.com/drupal/example.com'],
      [new Request(['destination' => 'example:com']), 'http://example.com/drupal/example:com'],
      [new Request(['destination' => 'javascript:alert(0)']), 'http://example.com/drupal/javascript:alert(0)'],
      [new Request(['destination' => 'http://example.com/drupal/']), 'http://example.com/drupal/'],
      [new Request(['destination' => 'http://example.com/drupal/test']), 'http://example.com/drupal/test'],
    ];
  }

  /**
   * @dataProvider providerTestDestinationRedirectToExternalUrl
   */
  public function testDestinationRedirectToExternalUrl($request, $expected) {
    $dispatcher = new EventDispatcher();
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $response = new RedirectResponse('http://other-example.com');

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $dispatcher->addListener(KernelEvents::RESPONSE, [$listener, 'checkRedirectUrl']);
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $this->setExpectedException(\PHPUnit_Framework_Error::class);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);
  }

  /**
   * @covers ::checkRedirectUrl
   */
  public function testRedirectWithOptInExternalUrl() {
    $dispatcher = new EventDispatcher();
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $response = new TrustedRedirectResponse('http://external-url.com');
    $request = Request::create('');
    $request->headers->set('HOST', 'example.com');

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $dispatcher->addListener(KernelEvents::RESPONSE, [$listener, 'checkRedirectUrl']);
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

    $target_url = $event->getResponse()->getTargetUrl();
    $this->assertEquals('http://external-url.com', $target_url);
  }

  /**
   * Data provider for testDestinationRedirectToExternalUrl().
   */
  public function providerTestDestinationRedirectToExternalUrl() {
    return [
      'absolute external url' => [new Request(['destination' => 'http://example.com']), 'http://example.com'],
      'absolute external url with folder' => [new Request(['destination' => 'http://example.com/foobar']), 'http://example.com/foobar'],
      'absolute external url with folder2' => [new Request(['destination' => 'http://example.ca/drupal']), 'http://example.ca/drupal'],
      'path without drupal basepath' => [new Request(['destination' => '/test']), 'http://example.com/test'],
      'path with URL' => [new Request(['destination' => '/example.com']), 'http://example.com/example.com'],
      'path with URL and two slashes' => [new Request(['destination' => '//example.com']), 'http://example.com//example.com'],
    ];
  }

  /**
   * @dataProvider providerTestDestinationRedirectWithInvalidUrl
   */
  public function testDestinationRedirectWithInvalidUrl(Request $request) {
    $dispatcher = new EventDispatcher();
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $response = new RedirectResponse('http://example.com/drupal');

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $dispatcher->addListener(KernelEvents::RESPONSE, [$listener, 'checkRedirectUrl']);
    $event = new FilterResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);
    $this->setExpectedException(\PHPUnit_Framework_Error::class);
    $dispatcher->dispatch(KernelEvents::RESPONSE, $event);
  }

  /**
   * Data provider for testDestinationRedirectWithInvalidUrl().
   */
  public function providerTestDestinationRedirectWithInvalidUrl() {
    $data = [];
    $data[] = [new Request(['destination' => '//example:com'])];
    $data[] = [new Request(['destination' => '//example:com/test'])];
    $data['absolute external url'] = [new Request(['destination' => 'http://example.com'])];
    $data['absolute external url with folder'] = [new Request(['destination' => 'http://example.ca/drupal'])];
    $data['path without drupal basepath'] = [new Request(['destination' => '/test'])];
    $data['path with URL'] = [new Request(['destination' => '/example.com'])];
    $data['path with URL and two slashes'] = [new Request(['destination' => '//example.com'])];

    return $data;
  }

  /**
   * Tests that $_GET only contain internal URLs.
   *
   * @covers ::sanitizeDestination
   *
   * @dataProvider providerTestSanitizeDestination
   *
   * @see \Drupal\Component\Utility\UrlHelper::isExternal
   */
  public function testSanitizeDestinationForGet($input, $output) {
    $request = new Request();
    $request->query->set('destination', $input);

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(KernelEvents::REQUEST, [$listener, 'sanitizeDestination'], 100);
    $dispatcher->dispatch(KernelEvents::REQUEST, $event);

    $this->assertEquals($output, $request->query->get('destination'));
  }

  /**
   * Tests that $_REQUEST['destination'] only contain internal URLs.
   *
   * @covers ::sanitizeDestination
   *
   * @dataProvider providerTestSanitizeDestination
   *
   * @see \Drupal\Component\Utility\UrlHelper::isExternal
   */
  public function testSanitizeDestinationForPost($input, $output) {
    $request = new Request();
    $request->request->set('destination', $input);

    $listener = new RedirectResponseSubscriber($this->urlAssembler, $this->requestContext);
    $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(KernelEvents::REQUEST, [$listener, 'sanitizeDestination'], 100);
    $dispatcher->dispatch(KernelEvents::REQUEST, $event);

    $this->assertEquals($output, $request->request->get('destination'));
  }

  /**
   * Data provider for testSanitizeDestination().
   */
  public function providerTestSanitizeDestination() {
    $data = [];
    // Standard internal example node path is present in the 'destination'
    // parameter.
    $data[] = ['node', 'node'];
    // Internal path with one leading slash is allowed.
    $data[] = ['/example.com', '/example.com'];
    // External URL without scheme is not allowed.
    $data[] = ['//example.com/test', ''];
    // Internal URL using a colon is allowed.
    $data[] = ['example:test', 'example:test'];
    // External URL is not allowed.
    $data[] = ['http://example.com', ''];
    // Javascript URL is allowed because it is treated as an internal URL.
    $data[] = ['javascript:alert(0)', 'javascript:alert(0)'];

    return $data;
  }

}
