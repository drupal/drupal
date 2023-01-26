<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\UrlTest.
 */

namespace Drupal\Tests\Core;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Url
 * @group UrlTest
 */
class UrlTest extends UnitTestCase {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathAliasManager;

  /**
   * The router.
   *
   * @var \Drupal\Tests\Core\Routing\TestRouterInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $router;

  /**
   * An array of values to use for the test.
   *
   * @var array
   */
  protected $map;

  /**
   * The mocked path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $map = [];
    $map[] = ['view.frontpage.page_1', [], [], FALSE, '/node'];
    $map[] = ['node_view', ['node' => '1'], [], FALSE, '/node/1'];
    $map[] = ['node_edit', ['node' => '2'], [], FALSE, '/node/2/edit'];
    $this->map = $map;

    $alias_map = [
      // Set up one proper alias that can be resolved to a system path.
      ['node-alias-test', NULL, FALSE, 'node'],
      // Passing in anything else should return the same string.
      ['node', NULL, FALSE, 'node'],
      ['node/1', NULL, FALSE, 'node/1'],
      ['node/2/edit', NULL, FALSE, 'node/2/edit'],
      ['non-existent', NULL, FALSE, 'non-existent'],
    ];

    // $this->map has $collect_bubbleable_metadata = FALSE; also generate the
    // $collect_bubbleable_metadata = TRUE case for ::generateFromRoute().
    $generate_from_route_map = [];
    foreach ($this->map as $values) {
      $generate_from_route_map[] = $values;
      $generate_from_route_map[] = [$values[0], $values[1], $values[2], TRUE, (new GeneratedUrl())->setGeneratedUrl($values[4])];
    }
    $this->urlGenerator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturnMap($generate_from_route_map);

    $this->pathAliasManager = $this->createMock('Drupal\path_alias\AliasManagerInterface');
    $this->pathAliasManager->expects($this->any())
      ->method('getPathByAlias')
      ->willReturnMap($alias_map);

    $this->router = $this->createMock('Drupal\Tests\Core\Routing\TestRouterInterface');
    $this->pathValidator = $this->createMock('Drupal\Core\Path\PathValidatorInterface');

    $this->container = new ContainerBuilder();
    $this->container->set('router.no_access_checks', $this->router);
    $this->container->set('url_generator', $this->urlGenerator);
    $this->container->set('path_alias.manager', $this->pathAliasManager);
    $this->container->set('path.validator', $this->pathValidator);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests creating a URL from a request.
   */
  public function testUrlFromRequest() {
    $this->router->expects($this->exactly(3))
      ->method('matchRequest')
      ->withConsecutive(
        [$this->getRequestConstraint('/node')],
        [$this->getRequestConstraint('/node/1')],
        [$this->getRequestConstraint('/node/2/edit')],
      )
      ->willReturnOnConsecutiveCalls([
        RouteObjectInterface::ROUTE_NAME => 'view.frontpage.page_1',
        '_raw_variables' => new InputBag(),
      ], [
        RouteObjectInterface::ROUTE_NAME => 'node_view',
        '_raw_variables' => new InputBag(['node' => '1']),
      ], [
        RouteObjectInterface::ROUTE_NAME => 'node_edit',
        '_raw_variables' => new InputBag(['node' => '2']),
      ]);

    $urls = [];
    foreach ($this->map as $index => $values) {
      $path = array_pop($values);
      $url = Url::createFromRequest(Request::create("$path"));
      $expected = Url::fromRoute($values[0], $values[1], $values[2]);
      $this->assertEquals($expected, $url);
      $urls[$index] = $url;
    }
    return $urls;
  }

  /**
   * This constraint checks whether a Request object has the right path.
   *
   * @param string $path
   *   The path.
   *
   * @return \PHPUnit\Framework\Constraint\Callback
   *   The constraint checks whether a Request object has the right path.
   */
  protected function getRequestConstraint($path) {
    return $this->callback(function (Request $request) use ($path) {
      return $request->getPathInfo() == $path;
    });
  }

  /**
   * Tests the fromRoute() method with the special <front> path.
   *
   * @covers ::fromRoute
   */
  public function testFromRouteFront() {
    $url = Url::fromRoute('<front>');
    $this->assertSame('<front>', $url->getRouteName());
  }

  /**
   * Tests the fromUserInput method with valid paths.
   *
   * @covers ::fromUserInput
   * @dataProvider providerFromValidInternalUri
   */
  public function testFromUserInput($path) {
    $url = Url::fromUserInput($path);
    $uri = $url->getUri();

    $this->assertInstanceOf('Drupal\Core\Url', $url);
    $this->assertFalse($url->isRouted());
    $this->assertStringStartsWith('base:', $uri);

    $parts = UrlHelper::parse($path);
    $options = $url->getOptions();

    if (!empty($parts['fragment'])) {
      $this->assertSame($parts['fragment'], $options['fragment']);
    }
    else {
      $this->assertArrayNotHasKey('fragment', $options);
    }

    if (!empty($parts['query'])) {
      $this->assertEquals($parts['query'], $options['query']);
    }
    else {
      $this->assertArrayNotHasKey('query', $options);
    }
  }

  /**
   * Tests the fromUserInput method with invalid paths.
   *
   * @covers ::fromUserInput
   * @dataProvider providerFromInvalidInternalUri
   */
  public function testFromInvalidUserInput($path) {
    $this->expectException(\InvalidArgumentException::class);
    $url = Url::fromUserInput($path);
  }

  /**
   * Tests fromUri() method with a user-entered path not matching any route.
   *
   * @covers ::fromUri
   */
  public function testFromRoutedPathWithInvalidRoute() {
    $this->pathValidator->expects($this->once())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->with('invalid-path')
      ->willReturn(FALSE);
    $url = Url::fromUri('internal:/invalid-path');
    $this->assertFalse($url->isRouted());
    $this->assertSame('base:invalid-path', $url->getUri());
  }

  /**
   * Tests fromUri() method with user-entered path matching a valid route.
   *
   * @covers ::fromUri
   */
  public function testFromRoutedPathWithValidRoute() {
    $url = Url::fromRoute('test_route');
    $this->pathValidator->expects($this->once())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->with('valid-path')
      ->willReturn($url);
    $result_url = Url::fromUri('internal:/valid-path');
    $this->assertSame($url, $result_url);
  }

  /**
   * Tests the createFromRequest method.
   *
   * @covers ::createFromRequest
   */
  public function testCreateFromRequest() {
    $attributes = [
      '_raw_variables' => new InputBag([
        'color' => 'chartreuse',
      ]),
      RouteObjectInterface::ROUTE_NAME => 'the_route_name',
    ];
    $request = new Request([], [], $attributes);

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->willReturn($attributes);

    $url = Url::createFromRequest($request);
    $expected = new Url('the_route_name', ['color' => 'chartreuse']);
    $this->assertEquals($expected, $url);
  }

  /**
   * Tests that an invalid request will thrown an exception.
   *
   * @covers ::createFromRequest
   */
  public function testUrlFromRequestInvalid() {
    $request = Request::create('/test-path');

    $this->router->expects($this->once())
      ->method('matchRequest')
      ->with($request)
      ->will($this->throwException(new ResourceNotFoundException()));

    $this->expectException(ResourceNotFoundException::class);
    Url::createFromRequest($request);
  }

  /**
   * Tests the isExternal() method.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::isExternal
   */
  public function testIsExternal($urls) {
    foreach ($urls as $url) {
      $this->assertFalse($url->isExternal());
    }
  }

  /**
   * Tests the getUri() method for internal URLs.
   *
   * @param \Drupal\Core\Url[] $urls
   *   Array of URL objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getUri
   */
  public function testGetUriForInternalUrl($urls) {
    $this->expectException(\UnexpectedValueException::class);
    foreach ($urls as $url) {
      $url->getUri();
    }
  }

  /**
   * Tests the getUri() method for external URLs.
   *
   * @covers ::getUri
   */
  public function testGetUriForExternalUrl() {
    $url = Url::fromUri('http://example.com/test');
    $this->assertEquals('http://example.com/test', $url->getUri());
  }

  /**
   * Tests the getUri() and isExternal() methods for protocol-relative URLs.
   *
   * @covers ::getUri
   * @covers ::isExternal
   */
  public function testGetUriForProtocolRelativeUrl() {
    $url = Url::fromUri('//example.com/test');
    $this->assertEquals('//example.com/test', $url->getUri());
    $this->assertTrue($url->isExternal());
  }

  /**
   * Tests the getInternalPath method().
   *
   * @param \Drupal\Core\Url[] $urls
   *   Array of URL objects.
   *
   * @covers ::getInternalPath
   *
   * @depends testUrlFromRequest
   */
  public function testGetInternalPath($urls) {
    $map = [];
    $map[] = ['view.frontpage.page_1', [], '/node'];
    $map[] = ['node_view', ['node' => '1'], '/node/1'];
    $map[] = ['node_edit', ['node' => '2'], '/node/2/edit'];

    foreach ($urls as $url) {
      // Clone the URL so that there is no leak of internal state into the
      // other ones.
      $url = clone $url;
      $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
      $url_generator->expects($this->once())
        ->method('getPathFromRoute')
        ->willReturnMap($map);
      $url->setUrlGenerator($url_generator);

      $url->getInternalPath();
      $url->getInternalPath();
    }
  }

  /**
   * Tests the toString() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::toString
   */
  public function testToString($urls) {
    foreach ($urls as $index => $url) {
      $path = array_pop($this->map[$index]);
      $this->assertSame($path, $url->toString());
      $generated_url = $url->toString(TRUE);
      $this->assertSame($path, $generated_url->getGeneratedUrl());
      $this->assertInstanceOf('\Drupal\Core\Render\BubbleableMetadata', $generated_url);
    }
  }

  /**
   * Tests the getRouteName() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getRouteName
   */
  public function testGetRouteName($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][0], $url->getRouteName());
    }
  }

  /**
   * Tests the getRouteName() with an external URL.
   *
   * @covers ::getRouteName
   */
  public function testGetRouteNameWithExternalUrl() {
    $url = Url::fromUri('http://example.com');
    $this->expectException(\UnexpectedValueException::class);
    $url->getRouteName();
  }

  /**
   * Tests the getRouteParameters() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParameters($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][1], $url->getRouteParameters());
    }
  }

  /**
   * Tests the getRouteParameters() with an external URL.
   *
   * @covers ::getRouteParameters
   */
  public function testGetRouteParametersWithExternalUrl() {
    $url = Url::fromUri('http://example.com');
    $this->expectException(\UnexpectedValueException::class);
    $url->getRouteParameters();
  }

  /**
   * Tests the getOptions() method.
   *
   * @param \Drupal\Core\Url[] $urls
   *   An array of Url objects.
   *
   * @depends testUrlFromRequest
   *
   * @covers ::getOptions
   */
  public function testGetOptions($urls) {
    foreach ($urls as $index => $url) {
      $this->assertSame($this->map[$index][2], $url->getOptions());
    }
  }

  /**
   * Tests the setOptions() method.
   *
   * @covers ::setOptions
   */
  public function testSetOptions() {
    $url = Url::fromRoute('test_route', []);
    $this->assertEquals([], $url->getOptions());
    $url->setOptions(['foo' => 'bar']);
    $this->assertEquals(['foo' => 'bar'], $url->getOptions());
    $url->setOptions([]);
    $this->assertEquals([], $url->getOptions());
  }

  /**
   * Tests the mergeOptions() method.
   *
   * @covers ::mergeOptions
   */
  public function testMergeOptions() {
    $url = Url::fromRoute('test_route', [], ['foo' => 'bar', 'bar' => ['key' => 'value']]);
    $url->mergeOptions(['bar' => ['key' => 'value1', 'key2' => 'value2']]);
    $this->assertEquals(['foo' => 'bar', 'bar' => ['key' => 'value1', 'key2' => 'value2']], $url->getOptions());
  }

  /**
   * Tests the access() method for routed URLs.
   *
   * @param bool $access
   *   The access value.
   *
   * @covers ::access
   * @covers ::accessManager
   * @dataProvider accessProvider
   */
  public function testAccessRouted($access) {
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $url = new TestUrl('entity.node.canonical', ['node' => 3]);
    $url->setAccessManager($this->getMockAccessManager($access, $account));
    $this->assertEquals($access, $url->access($account));
  }

  /**
   * Tests the access() method for unrouted URLs (they always have access).
   *
   * @covers ::access
   */
  public function testAccessUnrouted() {
    $account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $url = TestUrl::fromUri('base:kittens');
    $access_manager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $access_manager->expects($this->never())
      ->method('checkNamedRoute');
    $url->setAccessManager($access_manager);
    $this->assertTrue($url->access($account));
  }

  /**
   * Tests the renderAccess() method.
   *
   * @param bool $access
   *   The access value.
   *
   * @covers ::renderAccess
   * @dataProvider accessProvider
   */
  public function testRenderAccess($access) {
    $element = [
      '#url' => Url::fromRoute('entity.node.canonical', ['node' => 3]),
    ];
    $this->container->set('current_user', $this->createMock('Drupal\Core\Session\AccountInterface'));
    $this->container->set('access_manager', $this->getMockAccessManager($access));
    $this->assertEquals($access, TestUrl::renderAccess($element));
  }

  /**
   * Tests the fromRouteMatch() method.
   */
  public function testFromRouteMatch() {
    $route = new Route('/test-route/{foo}');
    $route_match = new RouteMatch('test_route', $route, ['foo' => (object) [1]], ['foo' => 1]);
    $url = Url::fromRouteMatch($route_match);
    $this->assertSame('test_route', $url->getRouteName());
    $this->assertEquals(['foo' => '1'], $url->getRouteParameters());
  }

  /**
   * Data provider for testing entity URIs.
   */
  public function providerTestEntityUris() {
    return [
      [
        'entity:test_entity/1',
        [],
        'entity.test_entity.canonical',
        ['test_entity' => '1'],
        NULL,
        NULL,
      ],
      [
        // Ensure a fragment of #0 is handled correctly.
        'entity:test_entity/1#0',
        [],
        'entity.test_entity.canonical',
        ['test_entity' => '1'],
        NULL,
        '0',
      ],
      // Ensure an empty fragment of # is in options discarded as expected.
      [
        'entity:test_entity/1',
        ['fragment' => ''],
        'entity.test_entity.canonical',
        ['test_entity' => '1'],
        NULL,
        NULL,
      ],
      // Ensure an empty fragment of # in the URI is discarded as expected.
      [
        'entity:test_entity/1#',
        [],
        'entity.test_entity.canonical',
        ['test_entity' => '1'],
        NULL,
        NULL,
      ],
      [
        'entity:test_entity/2?page=1&foo=bar#bottom',
        [], 'entity.test_entity.canonical',
        ['test_entity' => '2'],
        ['page' => '1', 'foo' => 'bar'],
        'bottom',
      ],
      [
        'entity:test_entity/2?page=1&foo=bar#bottom',
        ['fragment' => 'top', 'query' => ['foo' => 'yes', 'focus' => 'no']],
        'entity.test_entity.canonical',
        ['test_entity' => '2'],
        ['page' => '1', 'foo' => 'yes', 'focus' => 'no'],
        'top',
      ],

    ];
  }

  /**
   * Tests the fromUri() method with an entity: URI.
   *
   * @covers ::fromUri
   *
   * @dataProvider providerTestEntityUris
   */
  public function testEntityUris($uri, $options, $route_name, $route_parameters, $query, $fragment) {
    $url = Url::fromUri($uri, $options);
    $this->assertSame($route_name, $url->getRouteName());
    $this->assertEquals($route_parameters, $url->getRouteParameters());
    $this->assertEquals($url->getOption('query'), $query);
    $this->assertSame($url->getOption('fragment'), $fragment);
  }

  /**
   * Tests the fromUri() method with an invalid entity: URI.
   *
   * @covers ::fromUri
   */
  public function testInvalidEntityUriParameter() {
    // Make the mocked URL generator behave like the actual one.
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('entity.test_entity.canonical', ['test_entity' => '1/blah'])
      ->willThrowException(new InvalidParameterException('Parameter "test_entity" for route "/test_entity/{test_entity}" must match "[^/]++" ("1/blah" given) to generate a corresponding URL..'));

    $this->expectException(InvalidParameterException::class);
    Url::fromUri('entity:test_entity/1/blah')->toString();
  }

  /**
   * Tests the toUriString() method with entity: URIs.
   *
   * @covers ::toUriString
   *
   * @dataProvider providerTestToUriStringForEntity
   */
  public function testToUriStringForEntity($uri, $options, $uri_string) {
    $url = Url::fromUri($uri, $options);
    $this->assertSame($url->toUriString(), $uri_string);
  }

  /**
   * Data provider for testing string entity URIs.
   */
  public function providerTestToUriStringForEntity() {
    return [
      ['entity:test_entity/1', [], 'route:entity.test_entity.canonical;test_entity=1'],
      ['entity:test_entity/1', ['fragment' => 'top', 'query' => ['page' => '2']], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],
      ['entity:test_entity/1?page=2#top', [], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],
    ];
  }

  /**
   * Tests the toUriString() method with internal: URIs.
   *
   * @covers ::toUriString
   *
   * @dataProvider providerTestToUriStringForInternal
   */
  public function testToUriStringForInternal($uri, $options, $uri_string) {
    $url = Url::fromRoute('entity.test_entity.canonical', ['test_entity' => '1']);
    $this->pathValidator->expects($this->any())
      ->method('getUrlIfValidWithoutAccessCheck')
      ->willReturnMap([
        ['test-entity/1', $url],
        ['<front>', Url::fromRoute('<front>')],
        ['<none>', Url::fromRoute('<none>')],
      ]);
    $url = Url::fromUri($uri, $options);
    $this->assertSame($url->toUriString(), $uri_string);
  }

  /**
   * Data provider for testing internal URIs.
   */
  public function providerTestToUriStringForInternal() {
    return [
      // The four permutations of a regular path.
      ['internal:/test-entity/1', [], 'route:entity.test_entity.canonical;test_entity=1'],
      ['internal:/test-entity/1', ['fragment' => 'top'], 'route:entity.test_entity.canonical;test_entity=1#top'],
      ['internal:/test-entity/1', ['fragment' => 'top', 'query' => ['page' => '2']], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],
      ['internal:/test-entity/1?page=2#top', [], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],

      // The four permutations of the special '<front>' path.
      ['internal:/', [], 'route:<front>'],
      ['internal:/', ['fragment' => 'top'], 'route:<front>#top'],
      ['internal:/', ['fragment' => 'top', 'query' => ['page' => '2']], 'route:<front>?page=2#top'],
      ['internal:/?page=2#top', [], 'route:<front>?page=2#top'],

      // The four permutations of the special '<none>' path.
      ['internal:', [], 'route:<none>'],
      ['internal:', ['fragment' => 'top'], 'route:<none>#top'],
      ['internal:', ['fragment' => 'top', 'query' => ['page' => '2']], 'route:<none>?page=2#top'],
      ['internal:?page=2#top', [], 'route:<none>?page=2#top'],
    ];
  }

  /**
   * Tests the fromUri() method with a valid internal: URI.
   *
   * @covers ::fromUri
   * @dataProvider providerFromValidInternalUri
   */
  public function testFromValidInternalUri($path) {
    $url = Url::fromUri('internal:' . $path);
    $this->assertInstanceOf('Drupal\Core\Url', $url);
  }

  /**
   * Data provider for testFromValidInternalUri().
   */
  public function providerFromValidInternalUri() {
    return [
      // Normal paths with a leading slash.
      ['/kittens'],
      ['/kittens/bengal'],
      // Fragments with and without leading slashes.
      ['/#about-our-kittens'],
      ['/kittens#feeding'],
      ['#feeding'],
      // Query strings with and without leading slashes.
      ['/kittens?page=1000'],
      ['/?page=1000'],
      ['?page=1000'],
      ['?breed=bengal&page=1000'],
      ['?referrer=https://kittenfacts'],
      // Paths with various token formats but no leading slash.
      ['/[duckies]'],
      ['/%bunnies'],
      ['/{{ puppies }}'],
      // Disallowed characters in the authority (host name) that are valid
      // elsewhere in the path.
      ['/(:;2&+h^'],
      ['/AKI@&hO@'],
    ];
  }

  /**
   * Tests the fromUri() method with an invalid internal: URI.
   *
   * @covers ::fromUri
   * @dataProvider providerFromInvalidInternalUri
   */
  public function testFromInvalidInternalUri($path) {
    $this->expectException(\InvalidArgumentException::class);
    Url::fromUri('internal:' . $path);
  }

  /**
   * Data provider for testFromInvalidInternalUri().
   */
  public function providerFromInvalidInternalUri() {
    return [
      // Normal paths without a leading slash.
      'normal_path0' => ['kittens'],
      'normal_path1' => ['kittens/bengal'],
      // Path without a leading slash containing a fragment.
      'fragment' => ['kittens#feeding'],
       // Path without a leading slash containing a query string.
      'without_leading_slash_query' => ['kittens?page=1000'],
      // Paths with various token formats but no leading slash.
      'path_with_tokens0' => ['[duckies]'],
      'path_with_tokens1' => ['%bunnies'],
      'path_with_tokens2' => ['{{ puppies }}'],
      // Disallowed characters in the authority (host name) that are valid
      // elsewhere in the path.
      'disallowed_hostname_chars0' => ['(:;2&+h^'],
      'disallowed_hostname_chars1' => ['AKI@&hO@'],
      // Leading slash with a domain.
      'leading_slash_with_domain' => ['/http://example.com'],
    ];
  }

  /**
   * Tests the fromUri() method with a base: URI starting with a number.
   *
   * @covers ::fromUri
   */
  public function testFromUriNumber() {
    $url = Url::fromUri('base:2015/10/06');
    $this->assertSame($url->toUriString(), 'base:/2015/10/06');
  }

  /**
   * Tests the toUriString() method with route: URIs.
   *
   * @covers ::toUriString
   *
   * @dataProvider providerTestToUriStringForRoute
   */
  public function testToUriStringForRoute($uri, $options, $uri_string) {
    $url = Url::fromUri($uri, $options);
    $this->assertSame($url->toUriString(), $uri_string);
  }

  /**
   * Data provider for testing route: URIs.
   */
  public function providerTestToUriStringForRoute() {
    return [
      ['route:entity.test_entity.canonical;test_entity=1', [], 'route:entity.test_entity.canonical;test_entity=1'],
      ['route:entity.test_entity.canonical;test_entity=1', ['fragment' => 'top', 'query' => ['page' => '2']], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],
      ['route:entity.test_entity.canonical;test_entity=1?page=2#top', [], 'route:entity.test_entity.canonical;test_entity=1?page=2#top'],
      // Check that an empty fragment is discarded.
      ['route:entity.test_entity.canonical;test_entity=1?page=2#', [], 'route:entity.test_entity.canonical;test_entity=1?page=2'],
      // Check that an empty fragment is discarded.
      ['route:entity.test_entity.canonical;test_entity=1?page=2', ['fragment' => ''], 'route:entity.test_entity.canonical;test_entity=1?page=2'],
      // Check that a fragment of #0 is preserved.
      ['route:entity.test_entity.canonical;test_entity=1?page=2#0', [], 'route:entity.test_entity.canonical;test_entity=1?page=2#0'],
    ];
  }

  /**
   * @covers ::fromUri
   */
  public function testFromRouteUriWithMissingRouteName() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage("The route URI 'route:' is invalid.");
    Url::fromUri('route:');
  }

  /**
   * Creates a mock access manager for the access tests.
   *
   * @param bool $access
   *   The access value.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account to test.
   *
   * @return \Drupal\Core\Access\AccessManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getMockAccessManager($access, $account = NULL) {
    $access_manager = $this->createMock('Drupal\Core\Access\AccessManagerInterface');
    $access_manager->expects($this->once())
      ->method('checkNamedRoute')
      ->with('entity.node.canonical', ['node' => 3], $account)
      ->willReturn($access);
    return $access_manager;
  }

  /**
   * Data provider for the access test methods.
   */
  public function accessProvider() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

}

class TestUrl extends Url {

  /**
   * Sets the access manager.
   *
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   */
  public function setAccessManager(AccessManagerInterface $access_manager) {
    $this->accessManager = $access_manager;
  }

}
