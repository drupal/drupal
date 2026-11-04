<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Unit\Breadcrumbs;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\system\PathBasedBreadcrumbBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\system\PathBasedBreadcrumbBuilder.
 */
#[CoversClass(PathBasedBreadcrumbBuilder::class)]
#[Group('system')]
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
   * @var \Drupal\Core\Controller\TitleResolverInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $titleResolver;

  /**
   * The mocked access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $accessManager;

  /**
   * The request matching mock object.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $requestMatcher;

  /**
   * The mocked route request context.
   *
   * @var \Drupal\Core\Routing\RequestContext|\PHPUnit\Framework\MockObject\Stub
   */
  protected $context;

  /**
   * The mocked path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $pathProcessor;

  /**
   * The mocked path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $pathMatcher;

  /**
   * {@inheritdoc}
   *
   * @see ::__construct
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestMatcher = $this->createStub(RequestMatcherInterface::class);

    $config_factory = $this->getConfigFactoryStub(['system.site' => ['page' => ['front' => 'test_frontpage']]]);

    $this->pathProcessor = $this->createStub(InboundPathProcessorInterface::class);
    $this->context = $this->createStub(RequestContext::class);

    $this->accessManager = $this->createStub(AccessManagerInterface::class);
    $this->titleResolver = $this->createStub(TitleResolverInterface::class);
    $this->titleResolver
      ->method('getTitle')
      ->willReturnCallback(function (Request $request, Route $route) {
        return $route->getDefault('_title');
      });

    $this->pathMatcher = $this->createStub(PathMatcherInterface::class);

    $this->builder = new TestPathBasedBreadcrumbBuilder(
      $this->context,
      $this->accessManager,
      $this->requestMatcher,
      $this->pathProcessor,
      $config_factory,
      $this->titleResolver,
      $this->createStub(AccountInterface::class),
      $this->createStub(CurrentPathStack::class),
      $this->pathMatcher
    );

    $this->builder->setStringTranslation($this->getStringTranslationStub());

    $cache_contexts_manager = $this->createStub(CacheContextsManager::class);
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new Container();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the build method on the frontpage.
   */
  public function testBuildOnFrontpage(): void {
    $this->pathMatcher
      ->method('isFrontPage')
      ->willReturn(TRUE);

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['url.path.is_front', 'url.path.parent'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with one path element.
   */
  public function testBuildWithOnePathElement(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example');

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([0 => new Link('Home', new Url('<front>'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['url.path.is_front', 'url.path.parent'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with two path elements.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithTwoPathElements(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example/baz');
    $this->setupStubPathProcessor();

    $route_1 = new Route('/example', ['_title' => 'Example']);

    $this->requestMatcher
      ->method('matchRequest')
      ->willReturnCallback(function (Request $request) use ($route_1) {
        if ($request->getPathInfo() == '/example') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'example',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new InputBag([]),
          ];
        }
      });

    $this->setupAccessManagerToAllow();

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([0 => new Link('Home', new Url('<front>')), 1 => new Link('Example', new Url('example'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing([
      'url.path.is_front',
      'url.path.parent',
      'user.permissions',
    ], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with three path elements.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithThreePathElements(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example/bar/baz');
    $this->setupStubPathProcessor();

    $route_1 = new Route('/example/bar', ['_title' => 'Bar']);
    $route_2 = new Route('/example', ['_title' => 'Example']);

    $this->requestMatcher
      ->method('matchRequest')
      ->willReturnCallback(function (Request $request) use ($route_1, $route_2) {
        if ($request->getPathInfo() == '/example/bar') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'example_bar',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new InputBag([]),
          ];
        }
        elseif ($request->getPathInfo() == '/example') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'example',
            RouteObjectInterface::ROUTE_OBJECT => $route_2,
            '_raw_variables' => new InputBag([]),
          ];
        }
      });

    $this->accessManager
      ->method('check')
      ->willReturnOnConsecutiveCalls(
        AccessResult::allowed()->cachePerPermissions(),
        AccessResult::allowed()->addCacheContexts(['bar'])->addCacheTags(['example'])
      );
    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([
      new Link('Home', new Url('<front>')),
      new Link('Example', new Url('example')),
      new Link('Bar', new Url('example_bar')),
    ], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing([
      'bar',
      'url.path.is_front',
      'url.path.parent',
      'user.permissions',
    ], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing(['example'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with a NULL title route.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithNullTitle(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example/bar/baz');
    $this->setupStubPathProcessor();

    $route_1 = new Route('/example/bar', ['_title' => 'Bar']);
    $route_2 = new Route('/example', ['_title' => NULL]);

    $this->requestMatcher
      ->method('matchRequest')
      ->willReturnCallback(function (Request $request) use ($route_1, $route_2) {
        if ($request->getPathInfo() == '/example/bar') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'example_bar',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new InputBag([]),
          ];
        }
        elseif ($request->getPathInfo() == '/example') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'example',
            RouteObjectInterface::ROUTE_OBJECT => $route_2,
            '_raw_variables' => new InputBag([]),
          ];
        }
      });

    $this->accessManager
      ->method('check')
      ->willReturnOnConsecutiveCalls(
        AccessResult::allowed()->cachePerPermissions(),
        AccessResult::allowed()->addCacheContexts(['bar'])->addCacheTags(['example'])
      );
    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([
      new Link('Home', new Url('<front>')),
      new Link('Bar', new Url('example_bar')),
    ], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing([
      'bar',
      'url.path.is_front',
      'url.path.parent',
      'user.permissions',
    ], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing(['example'], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests that exceptions during request matching are caught.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  #[DataProvider('providerTestBuildWithException')]
  public function testBuildWithException(string $exception_class, string|array $exception_argument): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example/bar');
    $this->setupStubPathProcessor();

    $this->requestMatcher
      ->method('matchRequest')
      ->will($this->throwException(new $exception_class($exception_argument)));

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));

    // No path matched, though at least the frontpage is displayed.
    $this->assertEquals([0 => new Link('Home', new Url('<front>'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['url.path.is_front', 'url.path.parent'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Provides exception types for testBuildWithException.
   *
   * @return array
   *   The list of exception test cases.
   *
   * @see \Drupal\Tests\system\Unit\Breadcrumbs\PathBasedBreadcrumbBuilderTest::testBuildWithException()
   */
  public static function providerTestBuildWithException(): array {
    return [
      ['Drupal\Core\ParamConverter\ParamNotConvertedException', ''],
      ['Symfony\Component\Routing\Exception\MethodNotAllowedException', []],
      ['Symfony\Component\Routing\Exception\ResourceNotFoundException', ''],
    ];
  }

  /**
   * Tests the build method with a non processed path.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithNonProcessedPath(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/example/bar');

    $this->pathProcessor
      ->method('processInbound')
      ->willReturn(FALSE);

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));

    // No path matched, though at least the frontpage is displayed.
    $this->assertEquals([0 => new Link('Home', new Url('<front>'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['url.path.is_front', 'url.path.parent'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the build method with an invalid path.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithInvalidPath(): void {
    // The parse_url() function returns FALSE for '/:123/foo' so the
    // Request::create() method therefore considers it to be an invalid URI.
    $this->context
      ->method('getPathInfo')
      ->willReturn('/:123/foo/bar');

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));

    // No path matched, though at least the frontpage is displayed.
    $this->assertEquals([0 => new Link('Home', new Url('<front>'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing(['url.path.is_front', 'url.path.parent'], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Tests the applied method.
   */
  public function testApplies(): void {
    $this->assertTrue($this->builder->applies($this->createStub(RouteMatchInterface::class), new CacheableMetadata()));
  }

  /**
   * Tests the breadcrumb for a user path.
   *
   * @see ::build
   * @see ::getRequestForPath
   */
  public function testBuildWithUserPath(): void {
    $this->context
      ->method('getPathInfo')
      ->willReturn('/user/1/edit');
    $this->setupStubPathProcessor();

    $route_1 = new Route('/user/1', ['_title' => 'Admin']);

    $this->requestMatcher
      ->method('matchRequest')
      ->willReturnCallback(function (Request $request) use ($route_1) {
        if ($request->getPathInfo() == '/user/1') {
          return [
            RouteObjectInterface::ROUTE_NAME => 'user_page',
            RouteObjectInterface::ROUTE_OBJECT => $route_1,
            '_raw_variables' => new InputBag([]),
          ];
        }
      });

    $this->setupAccessManagerToAllow();

    $breadcrumb = $this->builder->build($this->createStub(RouteMatchInterface::class));
    $this->assertEquals([0 => new Link('Home', new Url('<front>')), 1 => new Link('Admin', new Url('user_page'))], $breadcrumb->getLinks());
    $this->assertEqualsCanonicalizing([
      'url.path.is_front',
      'url.path.parent',
      'user.permissions',
    ], $breadcrumb->getCacheContexts());
    $this->assertEqualsCanonicalizing([], $breadcrumb->getCacheTags());
    $this->assertEquals(Cache::PERMANENT, $breadcrumb->getCacheMaxAge());
  }

  /**
   * Setup the access manager to always allow access to routes.
   */
  public function setupAccessManagerToAllow(): void {
    $this->accessManager
      ->method('check')
      ->willReturn((new AccessResultAllowed())->cachePerPermissions());
  }

  /**
   * Prepares the mock processInbound() method.
   */
  protected function setupStubPathProcessor(): void {
    $this->pathProcessor
      ->method('processInbound')
      ->willReturnArgument(0);
  }

}

/**
 * Helper class for testing purposes only.
 */
class TestPathBasedBreadcrumbBuilder extends PathBasedBreadcrumbBuilder {

  /**
   * {@inheritdoc}
   */
  public function setStringTranslation(TranslationInterface $string_translation): static {
    $this->stringTranslation = $string_translation;
    return $this;
  }

}
