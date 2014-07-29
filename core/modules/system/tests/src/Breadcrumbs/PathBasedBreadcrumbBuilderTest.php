<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Breadcrumbs\PathBasedBreadcrumbBuilderTest
 */

namespace Drupal\system\Tests\Breadcrumbs;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\system\PathBasedBreadcrumbBuilder
 * @group system
 */
class PathBasedBreadcrumbBuilderTest extends UnitTestCase {

  /**
   * The path based breadcrumb builder object to test.
   *
   * @var \Drupal\system\PathBasedBreadcrumbBuilder
   */
  protected $builder;

  /**
   * The mocked title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $titleResolver;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $accessManager;

  /**
   * The request matching mock object.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestMatcher;

  /**
   * The mocked route request context.
   *
   * @var \Symfony\Component\Routing\RequestContext|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $context;

  /**
   * The mocked link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $linkGenerator;

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The mocked path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $pathProcessor;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct()
   */
  public function setUp() {
    parent::setUp();

    $this->requestMatcher = $this->getMock('\Symfony\Component\Routing\Matcher\RequestMatcherInterface');

    $config_factory = $this->getConfigFactoryStub(array('system.site' => array('front' => 'test_frontpage')));

    $this->pathProcessor = $this->getMock('\Drupal\Core\PathProcessor\InboundPathProcessorInterface');
    $this->context = $this->getMock('\Symfony\Component\Routing\RequestContext');

    $this->accessManager = $this->getMock('\Drupal\Core\Access\AccessManagerInterface');
    $this->titleResolver = $this->getMock('\Drupal\Core\Controller\TitleResolverInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->builder = new TestPathBasedBreadcrumbBuilder(
      $this->context,
      $this->accessManager,
      $this->requestMatcher,
      $this->pathProcessor,
      $config_factory,
      $this->titleResolver,
      $this->currentUser
    );

    $this->builder->setStringTranslation($this->getStringTranslationStub());

    $this->linkGenerator = $this->getMock('Drupal\Core\Utility\LinkGeneratorInterface');
    $this->builder->setLinkGenerator($this->linkGenerator);
  }

  /**
   * Tests the build method on the frontpage.
   *
   * @covers ::build()
   */
  public function testBuildOnFrontpage() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/'));

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(), $links);
  }

  /**
   * Tests the build method with one path element.
   *
   * @covers ::build()
   */
  public function testBuildWithOnePathElement() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/example'));

    $this->setupLinkGeneratorWithFrontpage();

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(0 => '<a href="/">Home</a>'), $links);
  }

  /**
   * Tests the build method with two path elements.
   *
   * @covers ::build()
   * @covers ::getRequestForPath()
   */
  public function testBuildWithTwoPathElements() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/example/baz'));
    $this->setupStubPathProcessor();

    $route_1 = new Route('/example');

    $this->requestMatcher->expects($this->exactly(1))
      ->method('matchRequest')
      ->will($this->returnCallback(function(Request $request) use ($route_1) {
        if ($request->getPathInfo() == '/example') {
          return array(
            RouteObjectInterface::ROUTE_NAME => 'example',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new ParameterBag(array()),
          );
        }
      }));

    $link_example = '<a href="/example">Example</a>';
    $link_front = '<a href="/">Home</a>';
    $this->linkGenerator->expects($this->at(0))
      ->method('generate')
      ->with('Example', 'example', array(), array('html' => TRUE))
      ->will($this->returnValue($link_example));
    $this->linkGenerator->expects($this->at(1))
      ->method('generate')
      ->with('Home', '<front>', array(), array())
      ->will($this->returnValue($link_front));
    $this->setupAccessManagerWithTrue();

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(0 => '<a href="/">Home</a>', 1 => $link_example), $links);
  }

  /**
   * Tests the build method with three path elements.
   *
   * @covers ::build()
   * @covers ::getRequestForPath()
   */
  public function testBuildWithThreePathElements() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/example/bar/baz'));
    $this->setupStubPathProcessor();

    $route_1 = new Route('/example/bar');
    $route_2 = new Route('/example');

    $this->requestMatcher->expects($this->exactly(2))
      ->method('matchRequest')
      ->will($this->returnCallback(function(Request $request) use ($route_1, $route_2) {
        if ($request->getPathInfo() == '/example/bar') {
          return array(
            RouteObjectInterface::ROUTE_NAME => 'example_bar',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new ParameterBag(array()),
          );
        }
        elseif ($request->getPathInfo() == '/example') {
          return array(
            RouteObjectInterface::ROUTE_NAME => 'example',
            RouteObjectInterface::ROUTE_OBJECT => $route_2,
            '_raw_variables' => new ParameterBag(array()),
          );
        }
      }));

    $link_example_bar = '<a href="/example/bar">Bar</a>';
    $link_example = '<a href="/example">Example</a>';
    $link_front = '<a href="/">Home</a>';
    $this->linkGenerator->expects($this->at(0))
      ->method('generate')
      ->with('Bar', 'example_bar', array(), array('html' => TRUE))
      ->will($this->returnValue($link_example_bar));

    $this->linkGenerator->expects($this->at(1))
      ->method('generate')
      ->with('Example', 'example', array(), array('html' => TRUE))
      ->will($this->returnValue($link_example));
    $this->linkGenerator->expects($this->at(2))
      ->method('generate')
      ->with('Home', '<front>', array(), array())
      ->will($this->returnValue($link_front));
    $this->setupAccessManagerWithTrue();

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(0 => '<a href="/">Home</a>', 1 => $link_example, 2 => $link_example_bar), $links);
  }

  /**
   * Tests that exceptions during request matching are caught.
   *
   * @covers ::build()
   * @covers ::getRequestForPath()
   *
   * @dataProvider providerTestBuildWithException
   */
  public function testBuildWithException($exception_class, $exception_argument) {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/example/bar'));
    $this->setupStubPathProcessor();

    $this->requestMatcher->expects($this->any())
      ->method('matchRequest')
      ->will($this->throwException(new $exception_class($exception_argument)));
    $this->setupLinkGeneratorWithFrontpage();

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));

    // No path matched, though at least the frontpage is displayed.
    $this->assertEquals(array(0 => '<a href="/">Home</a>'), $links);
  }

  /**
   * Provides exception types for testBuildWithException.
   *
   * @return array
   *   The list of exception test cases.
   *
   * @see \Drupal\system\Tests\Breadcrumbs\PathBasedBreadcrumbBuilderTest::testBuildWithException()
   */
  public function providerTestBuildWithException() {
    return array(
      array('Drupal\Core\ParamConverter\ParamNotConvertedException', ''),
      array('Symfony\Component\Routing\Exception\MethodNotAllowedException', array()),
      array('Symfony\Component\Routing\Exception\ResourceNotFoundException', ''),
    );
  }

  /**
   * Tests the build method with a non processed path.
   *
   * @covers ::build()
   * @covers ::getRequestForPath()
   */
  public function testBuildWithNonProcessedPath() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/example/bar'));

    $this->pathProcessor->expects($this->once())
      ->method('processInbound')
      ->will($this->returnValue(FALSE));

    $this->requestMatcher->expects($this->any())
      ->method('matchRequest')
      ->will($this->returnValue(array()));
    $this->setupLinkGeneratorWithFrontpage();

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));

    // No path matched, though at least the frontpage is displayed.
    $this->assertEquals(array(0 => '<a href="/">Home</a>'), $links);
  }

  /**
   * Tests the applied method.
   *
   * @covers ::applies()
   */
  public function testApplies() {
    $this->assertTrue($this->builder->applies($this->getMock('Drupal\Core\Routing\RouteMatchInterface')));
  }

  /**
   * Tests the breadcrumb for a user path.
   *
   * @covers ::build()
   * @covers ::getRequestForPath()
   */
  public function testBuildWithUserPath() {
    $this->context->expects($this->once())
      ->method('getPathInfo')
      ->will($this->returnValue('/user/1/edit'));
    $this->setupStubPathProcessor();

    $route_1 = new Route('/user/1');

    $this->requestMatcher->expects($this->exactly(1))
      ->method('matchRequest')
      ->will($this->returnCallback(function(Request $request) use ($route_1) {
        if ($request->getPathInfo() == '/user/1') {
          return array(
            RouteObjectInterface::ROUTE_NAME => 'user_page',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new ParameterBag(array()),
          );
        }
      }));

    $link_user = '<a href="/user/1">Admin</a>';
    $link_front = '<a href="/">Home</a>';
    $this->linkGenerator->expects($this->at(0))
      ->method('generate')
      ->with('Admin', 'user_page', array(), array('html' => TRUE))
      ->will($this->returnValue($link_user));

    $this->linkGenerator->expects($this->at(1))
      ->method('generate')
      ->with('Home', '<front>', array(), array())
      ->will($this->returnValue($link_front));
    $this->setupAccessManagerWithTrue();
    $this->titleResolver->expects($this->once())
      ->method('getTitle')
      ->with($this->anything(), $route_1)
      ->will($this->returnValue('Admin'));

    $links = $this->builder->build($this->getMock('Drupal\Core\Routing\RouteMatchInterface'));
    $this->assertEquals(array(0 => '<a href="/">Home</a>', 1 => $link_user), $links);
  }

  /**
   * Setup the link generator with a frontpage route.
   */
  public function setupLinkGeneratorWithFrontpage() {
    $this->linkGenerator->expects($this->once())
      ->method('generate')
      ->with($this->anything(), '<front>', array())
      ->will($this->returnCallback(function($title) {
        return '<a href="/">' . $title . '</a>';
      }));
  }

  /**
   * Setup the access manager to always return TRUE.
   */
  public function setupAccessManagerWithTrue() {
    $this->accessManager->expects($this->any())
      ->method('checkNamedRoute')
      ->will($this->returnValue(TRUE));
  }

  protected function setupStubPathProcessor() {
    $this->pathProcessor->expects($this->any())
      ->method('processInbound')
      ->will($this->returnArgument(0));
  }

}

/**
 * Helper class for testing purposes only.
 */
class TestPathBasedBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  public function setStringTranslation(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  public function setLinkGenerator(LinkGeneratorInterface $link_generator) {
    $this->linkGenerator = $link_generator;
  }

}
