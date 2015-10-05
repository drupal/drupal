<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Routing\UrlGeneratorTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Confirm that the UrlGenerator is functioning properly.
 *
 * @coversDefaultClass \Drupal\Core\Routing\UrlGenerator
 * @group Routing
 */
class UrlGeneratorTest extends UnitTestCase {

  /**
   * The url generator to test.
   *
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $generator;

  /**
   * The alias manager.
   *
   * @var \Drupal\Core\Path\AliasManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $aliasManager;

  /**
   * The mock route processor manager.
   *
   * @var \Drupal\Core\RouteProcessor\RouteProcessorManager|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $routeProcessorManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $cache_contexts_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_contexts_manager->method('assertValidTokens')->willReturn(TRUE);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($container);

    $routes = new RouteCollection();
    $first_route = new Route('/test/one');
    $second_route = new Route('/test/two/{narf}');
    $third_route = new Route('/test/two/');
    $fourth_route = new Route('/test/four', [], [], [], '', ['https']);
    $none_route = new Route('', [], [], ['_no_path' => TRUE]);

    $routes->add('test_1', $first_route);
    $routes->add('test_2', $second_route);
    $routes->add('test_3', $third_route);
    $routes->add('test_4', $fourth_route);
    $routes->add('<none>', $none_route);

    // Create a route provider stub.
    $provider = $this->getMockBuilder('Drupal\Core\Routing\RouteProvider')
      ->disableOriginalConstructor()
      ->getMock();
    // We need to set up return value maps for both the getRouteByName() and the
    // getRoutesByNames() method calls on the route provider. The parameters
    // are not passed in and default to an empty array.
    $route_name_return_map = $routes_names_return_map = array();
    $return_map_values = array(
      [
        'route_name' => 'test_1',
        'return' => $first_route,
      ],
      [
        'route_name' => 'test_2',
        'return' => $second_route,
      ],
      [
        'route_name' => 'test_3',
        'return' => $third_route,
      ],
      [
        'route_name' => 'test_4',
        'return' => $fourth_route,
      ],
      [
        'route_name' => '<none>',
        'return' => $none_route,
      ],
    );
    foreach ($return_map_values as $values) {
      $route_name_return_map[] = array($values['route_name'], $values['return']);
      $routes_names_return_map[] = array(array($values['route_name']), $values['return']);
    }
    $provider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->returnValueMap($route_name_return_map));
    $provider->expects($this->any())
      ->method('getRoutesByNames')
      ->will($this->returnValueMap($routes_names_return_map));

    // Create an alias manager stub.
    $alias_manager = $this->getMockBuilder('Drupal\Core\Path\AliasManager')
      ->disableOriginalConstructor()
      ->getMock();

    $alias_manager->expects($this->any())
      ->method('getAliasByPath')
      ->will($this->returnCallback(array($this, 'aliasManagerCallback')));

    $this->aliasManager = $alias_manager;

    $this->requestStack = new RequestStack();
    $request = Request::create('/some/path');
    $this->requestStack->push($request);

    $context = new RequestContext();
    $context->fromRequestStack($this->requestStack);

    $processor = new PathProcessorAlias($this->aliasManager);
    $processor_manager = new PathProcessorManager();
    $processor_manager->addOutbound($processor, 1000);

    $this->routeProcessorManager = $this->getMockBuilder('Drupal\Core\RouteProcessor\RouteProcessorManager')
      ->disableOriginalConstructor()
      ->getMock();

    $generator = new UrlGenerator($provider, $processor_manager, $this->routeProcessorManager, $this->requestStack, ['http', 'https']);
    $generator->setContext($context);
    $this->generator = $generator;
  }

  /**
   * Return value callback for the getAliasByPath() method on the mock alias
   * manager.
   *
   * Ensures that by default the call to getAliasByPath() will return the first
   * argument that was passed in. We special-case the paths for which we wish it
   * to return an actual alias.
   *
   * @return string
   */
  public function aliasManagerCallback() {
    $args = func_get_args();
    switch($args[0]) {
      case '/test/one':
        return '/hello/world';
      case '/test/two/5':
        return '/goodbye/cruel/world';
      case '/<front>':
        return '/';
      default:
        return $args[0];
    }
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGeneration() {
    $url = $this->generator->generate('test_1');
    $this->assertEquals('/hello/world', $url);
    // No cacheability to test; UrlGenerator::generate() doesn't support
    // collecting cacheability metadata.

    $this->routeProcessorManager->expects($this->exactly(3))
      ->method('processOutbound')
      ->with($this->anything());


    // Check that the two generate methods return the same result.
    $this->assertGenerateFromRoute('test_1', [], [], $url, (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $path = $this->generator->getPathFromRoute('test_1');
    $this->assertEquals('test/one', $path);
  }

  /**
   * Tests URL generation in a subdirectory.
   */
  public function testGetPathFromRouteWithSubdirectory() {
    $this->routeProcessorManager->expects($this->once())
      ->method('processOutbound');

    $path = $this->generator->getPathFromRoute('test_1');
    $this->assertEquals('test/one', $path);
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGenerationWithParameters() {
    $url = $this->generator->generate('test_2', array('narf' => '5'));
    $this->assertEquals('/goodbye/cruel/world', $url);
    // No cacheability to test; UrlGenerator::generate() doesn't support
    // collecting cacheability metadata.

    $this->routeProcessorManager->expects($this->exactly(7))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('fragment' => 'top');
    // Extra parameters should appear in the query string.
    $this->assertGenerateFromRoute('test_1', ['zoo' => 5], $options, '/hello/world?zoo=5#top', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $options = array('query' => array('page' => '1'), 'fragment' => 'bottom');
    $this->assertGenerateFromRoute('test_2', ['narf' => 5], $options, '/goodbye/cruel/world?page=1#bottom', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    // Changing the parameters, the route still matches but there is no alias.
    $this->assertGenerateFromRoute('test_2', ['narf' => 7], $options, '/test/two/7?page=1#bottom', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $path = $this->generator->getPathFromRoute('test_2', array('narf' => '5'));
    $this->assertEquals('test/two/5', $path);
  }

  /**
   * Confirms that generated routes will have aliased paths with options.
   *
   * @dataProvider providerTestAliasGenerationWithOptions
   */
  public function testAliasGenerationWithOptions($route_name, $route_parameters, $options, $expected) {
    $this->assertGenerateFromRoute($route_name, $route_parameters, $options, $expected, (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));
  }

  /**
   * Provides test data for testAliasGenerationWithOptions.
   */
  public function providerTestAliasGenerationWithOptions() {
    $data = [];
    // Extra parameters should appear in the query string.
    $data[] = [
      'test_1',
      ['zoo' => '5'],
      ['fragment' => 'top'],
      '/hello/world?zoo=5#top',
    ];
    $data[] = [
      'test_2',
      ['narf' => '5'],
      ['query' => ['page' => '1'], 'fragment' => 'bottom'],
      '/goodbye/cruel/world?page=1#bottom',
    ];
    // Changing the parameters, the route still matches but there is no alias.
    $data[] = [
      'test_2',
      ['narf' => '7'],
      ['query' => ['page' => '1'], 'fragment' => 'bottom'],
      '/test/two/7?page=1#bottom',
    ];
    // Query string values containing '/' should be decoded.
    $data[] = [
      'test_2',
      ['narf' => '7'],
      ['query' => ['page' => '1/2'], 'fragment' => 'bottom'],
      '/test/two/7?page=1/2#bottom',
    ];
    return $data;
  }

  /**
   * Tests URL generation from route with trailing start and end slashes.
   */
  public function testGetPathFromRouteTrailing() {
    $this->routeProcessorManager->expects($this->once())
      ->method('processOutbound');

    $path = $this->generator->getPathFromRoute('test_3');
    $this->assertEquals($path, 'test/two');
  }

  /**
   * Confirms that absolute URLs work with generated routes.
   */
  public function testAbsoluteURLGeneration() {
    $url = $this->generator->generate('test_1', array(), TRUE);
    $this->assertEquals('http://localhost/hello/world', $url);
    // No cacheability to test; UrlGenerator::generate() doesn't support
    // collecting cacheability metadata.

    $this->routeProcessorManager->expects($this->exactly(2))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('absolute' => TRUE, 'fragment' => 'top');
    // Extra parameters should appear in the query string.
    $this->assertGenerateFromRoute('test_1', ['zoo' => 5], $options, 'http://localhost/hello/world?zoo=5#top', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT)->setCacheContexts(['url.site']));
  }

  /**
   * Confirms that explicitly setting the base_url works with generated routes
   */
  public function testBaseURLGeneration() {
    $options = array('base_url' => 'http://www.example.com:8888');
    $this->assertGenerateFromRoute('test_1', [], $options, 'http://www.example.com:8888/hello/world', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $options = array('base_url' => 'http://www.example.com:8888', 'https' => TRUE);
    $this->assertGenerateFromRoute('test_1', [], $options, 'https://www.example.com:8888/hello/world', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $options = array('base_url' => 'https://www.example.com:8888', 'https' => FALSE);
    $this->assertGenerateFromRoute('test_1', [], $options, 'http://www.example.com:8888/hello/world', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));

    $this->routeProcessorManager->expects($this->exactly(2))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('base_url' => 'http://www.example.com:8888', 'fragment' => 'top');
    // Extra parameters should appear in the query string.
    $this->assertGenerateFromRoute('test_1', ['zoo' => 5], $options, 'http://www.example.com:8888/hello/world?zoo=5#top', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT));
  }

  /**
   * Test that the 'scheme' route requirement is respected during url generation.
   */
  public function testUrlGenerationWithHttpsRequirement() {
    $url = $this->generator->generate('test_4', array(), TRUE);
    $this->assertEquals('https://localhost/test/four', $url);
    // No cacheability to test; UrlGenerator::generate() doesn't support
    // collecting cacheability metadata.

    $this->routeProcessorManager->expects($this->exactly(2))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('absolute' => TRUE, 'https' => TRUE);
    $this->assertGenerateFromRoute('test_1', [], $options, 'https://localhost/hello/world', (new BubbleableMetadata())->setCacheMaxAge(Cache::PERMANENT)->setCacheContexts(['url.site']));
  }

  /**
   * Tests path-based URL generation.
   */
  public function testPathBasedURLGeneration() {
    $base_path = '/subdir';
    $base_url = 'http://www.example.com' . $base_path;

    foreach (array('', 'index.php/') as $script_path) {
      foreach (array(FALSE, TRUE) as $absolute) {
        // Setup a fake request which looks like a Drupal installed under the
        // subdir "subdir" on the domain www.example.com.
        // To reproduce the values install Drupal like that and use a debugger.
        $server = [
          'SCRIPT_NAME' => '/subdir/index.php',
          'SCRIPT_FILENAME' => $this->root . '/index.php',
          'SERVER_NAME' => 'http://www.example.com',
        ];
        $request = Request::create('/subdir/' . $script_path, 'GET', [], [], [], $server);
        $request->headers->set('host', ['www.example.com']);
        $this->requestStack->push($request);

        // Determine the expected bubbleable metadata.
        $expected_cacheability = (new BubbleableMetadata())
          ->setCacheContexts($absolute ? ['url.site'] : [])
          ->setCacheMaxAge(Cache::PERMANENT);

        // Get the expected start of the path string.
        $base = ($absolute ? $base_url . '/' : $base_path . '/') . $script_path;
        $url = $base . 'node/123';
        $result = $this->generator->generateFromPath('node/123', array('absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('node/123', array('absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));

        $url = $base . 'node/123#foo';
        $result = $this->generator->generateFromPath('node/123', array('fragment' => 'foo', 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('node/123', array('fragment' => 'foo', 'absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));

        $url = $base . 'node/123?foo';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));

        $url = $base . 'node/123?foo=bar&bar=baz';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => 'bar', 'bar' => 'baz'), 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('node/123', array('query' => array('foo' => 'bar', 'bar' => 'baz'), 'absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));

        $url = $base . 'node/123?foo#bar';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'fragment' => 'bar', 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'fragment' => 'bar', 'absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));

        $url = $base;
        $result = $this->generator->generateFromPath('<front>', array('absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
        $generated_url = $this->generator->generateFromPath('<front>', array('absolute' => $absolute), TRUE);
        $this->assertEquals($url, $generated_url->getGeneratedUrl(), "$url == $result");
        $this->assertEquals($expected_cacheability, BubbleableMetadata::createFromObject($generated_url));
      }
    }
  }

  /**
   * Tests generating a relative URL with no path.
   *
   * @param array $options
   *   An array of URL options.
   * @param string $expected_url
   *   The expected relative URL.
   *
   * @covers ::generateFromRoute
   *
   * @dataProvider providerTestNoPath
   */
  public function testNoPath($options, $expected_url) {
    $url = $this->generator->generateFromRoute('<none>', [], $options);
    $this->assertEquals($expected_url, $url);
  }

  /**
   * Data provider for ::testNoPath().
   */
  public function providerTestNoPath() {
    return [
      // Empty options.
      [[], ''],
      // Query parameters only.
      [['query' => ['foo' => 'bar']], '?foo=bar'],
      // Multiple query parameters.
      [['query' => ['foo' => 'bar', 'baz' => '']], '?foo=bar&baz='],
      // Fragment only.
      [['fragment' => 'foo'], '#foo'],
      // Query parameters and fragment.
      [['query' => ['bar' => 'baz'], 'fragment' => 'foo'], '?bar=baz#foo'],
      // Multiple query parameters and fragment.
      [['query' => ['bar' => 'baz', 'foo' => 'bar'], 'fragment' => 'foo'], '?bar=baz&foo=bar#foo'],
    ];
  }

  /**
   * Asserts \Drupal\Core\Routing\UrlGenerator::generateFromRoute()'s output.
   *
   * @param $route_name
   *   The route name to test.
   * @param array $route_parameters
   *   The route parameters to test.
   * @param array $options
   *   The options to test.
   * @param $expected_url
   *   The expected generated URL string.
   * @param \Drupal\Core\Render\BubbleableMetadata $expected_bubbleable_metadata
   *   The expected generated bubbleable metadata.
   */
  protected function assertGenerateFromRoute($route_name, array $route_parameters, array $options, $expected_url, BubbleableMetadata $expected_bubbleable_metadata) {
    // First, test with $collect_cacheability_metadata set to the default value.
    $url = $this->generator->generateFromRoute($route_name, $route_parameters, $options);
    $this->assertSame($expected_url, $url);

    // Second, test with it set to TRUE.
    $generated_url = $this->generator->generateFromRoute($route_name, $route_parameters, $options, TRUE);
    $this->assertSame($expected_url, $generated_url->getGeneratedUrl());
    $this->assertEquals($expected_bubbleable_metadata, BubbleableMetadata::createFromObject($generated_url));
  }

}
