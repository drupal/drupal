<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\UrlGeneratorTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;

/**
 * Basic tests for the Route.
 *
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
   * A second url generator to test, set to assume mixed-mode sessions.
   *
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $generatorMixedMode;

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

  public static function getInfo() {
    return array(
      'name' => 'UrlGenerator',
      'description' => 'Confirm that the UrlGenerator is functioning properly.',
      'group' => 'Routing',
    );
  }

  function setUp() {

    $routes = new RouteCollection();
    $first_route = new Route('/test/one');
    $second_route = new Route('/test/two/{narf}');
    $third_route = new Route('/test/two/');
    $fourth_route = new Route('/test/four', array(), array('_scheme' => 'https'));
    $routes->add('test_1', $first_route);
    $routes->add('test_2', $second_route);
    $routes->add('test_3', $third_route);
    $routes->add('test_4', $fourth_route);

    // Create a route provider stub.
    $provider = $this->getMockBuilder('Drupal\Core\Routing\RouteProvider')
      ->disableOriginalConstructor()
      ->getMock();
    // We need to set up return value maps for both the getRouteByName() and the
    // getRoutesByNames() method calls on the route provider. The parameters
    // are not passed in and default to an empty array.
    $route_name_return_map = $routes_names_return_map = array();
    $return_map_values = array(
      array(
        'route_name' => 'test_1',
        'return' => $first_route,
      ),
      array(
        'route_name' => 'test_2',
        'return' => $second_route,
      ),
      array(
        'route_name' => 'test_3',
        'return' => $third_route,
      ),
      array(
        'route_name' => 'test_4',
        'return' => $fourth_route,
      ),
    );
    foreach ($return_map_values as $values) {
      $route_name_return_map[] = array($values['route_name'], array(), $values['return']);
      $routes_names_return_map[] = array(array($values['route_name']), array(), $values['return']);
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

    $context = new RequestContext();
    $context->fromRequest(Request::create('/some/path'));

    $processor = new PathProcessorAlias($this->aliasManager);
    $processor_manager = new PathProcessorManager();
    $processor_manager->addOutbound($processor, 1000);

    $this->routeProcessorManager = $this->getMockBuilder('Drupal\Core\RouteProcessor\RouteProcessorManager')
      ->disableOriginalConstructor()
      ->getMock();

    $config_factory_stub = $this->getConfigFactoryStub(array('system.filter' => array('protocols' => array('http', 'https'))));

    $generator = new UrlGenerator($provider, $processor_manager, $this->routeProcessorManager, $config_factory_stub, new Settings(array()));
    $generator->setContext($context);
    $this->generator = $generator;

    // Second generator for mixed-mode sessions.
    $generator = new UrlGenerator($provider, $processor_manager, $this->routeProcessorManager, $config_factory_stub, new Settings(array('mixed_mode_sessions' => TRUE)));
    $generator->setContext($context);
    $this->generatorMixedMode = $generator;
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
      case 'test/one':
        return 'hello/world';
      case 'test/two/5':
        return 'goodbye/cruel/world';
      case '<front>':
        return '';
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

    $this->routeProcessorManager->expects($this->once())
      ->method('processOutbound')
      ->with($this->anything());


    // Check that the two generate methods return the same result.
    $url_from_route = $this->generator->generateFromRoute('test_1');
    $this->assertEquals($url_from_route, $url);

    $path = $this->generator->getPathFromRoute('test_1');
    $this->assertEquals('test/one', $path);
  }

  /**
   * Tests URL generation in a subdirectory.
   */
  public function testGetPathFromRouteWithSubdirectory() {
    $this->generator->setBasePath('/test-base-path');

    $this->routeProcessorManager->expects($this->never())
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

    $this->routeProcessorManager->expects($this->exactly(3))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('fragment' => 'top');
    // Extra parameters should appear in the query string.
    $url = $this->generator->generateFromRoute('test_1', array('zoo' => '5'), $options);
    $this->assertEquals('/hello/world?zoo=5#top', $url);

    $options = array('query' => array('page' => '1'), 'fragment' => 'bottom');
    $url = $this->generator->generateFromRoute('test_2', array('narf' => '5'), $options);
    $this->assertEquals('/goodbye/cruel/world?page=1#bottom', $url);

    // Changing the parameters, the route still matches but there is no alias.
    $url = $this->generator->generateFromRoute('test_2', array('narf' => '7'), $options);
    $this->assertEquals('/test/two/7?page=1#bottom', $url);

    $path = $this->generator->getPathFromRoute('test_2', array('narf' => '5'));
    $this->assertEquals('test/two/5', $path);
  }

  /**
   * Tests URL generation from route with trailing start and end slashes.
   */
  public function testGetPathFromRouteTrailing() {
    $this->routeProcessorManager->expects($this->never())
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

    $this->routeProcessorManager->expects($this->once())
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('absolute' => TRUE, 'fragment' => 'top');
    // Extra parameters should appear in the query string.
    $url = $this->generator->generateFromRoute('test_1', array('zoo' => '5'), $options);
    $this->assertEquals('http://localhost/hello/world?zoo=5#top', $url);
  }

  /**
   * Test that the 'scheme' route requirement is respected during url generation.
   */
  public function testUrlGenerationWithHttpsRequirement() {
    $url = $this->generator->generate('test_4', array(), TRUE);
    $this->assertEquals('https://localhost/test/four', $url);

    $this->routeProcessorManager->expects($this->exactly(2))
      ->method('processOutbound')
      ->with($this->anything());

    $options = array('absolute' => TRUE, 'https' => TRUE);
    // Mixed-mode sessions are not enabled, so the https option is ignored.
    $url = $this->generator->generateFromRoute('test_1', array(), $options);
    $this->assertEquals('http://localhost/hello/world', $url);

    // Mixed-mode sessions are enabled, so the https option is obeyed.
    $url = $this->generatorMixedMode->generateFromRoute('test_1', array(), $options);
    $this->assertEquals('https://localhost/hello/world', $url);
  }

  /**
   * Tests path-based URL generation.
   */
  public function testPathBasedURLGeneration() {
    $base_path = '/subdir';
    $base_url = 'http://www.example.com' . $base_path;
    $this->generator->setBasePath($base_path . '/');
    $this->generator->setBaseUrl($base_url . '/');
    foreach (array('', 'index.php/') as $script_path) {
      $this->generator->setScriptPath($script_path);
      foreach (array(FALSE, TRUE) as $absolute) {
        // Get the expected start of the path string.
        $base = ($absolute ? $base_url . '/' : $base_path . '/') . $script_path;
        $url = $base . 'node/123';
        $result = $this->generator->generateFromPath('node/123', array('absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");

        $url = $base . 'node/123#foo';
        $result = $this->generator->generateFromPath('node/123', array('fragment' => 'foo', 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");

        $url = $base . 'node/123?foo';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");

        $url = $base . 'node/123?foo=bar&bar=baz';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => 'bar', 'bar' => 'baz'), 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");

        $url = $base . 'node/123?foo#bar';
        $result = $this->generator->generateFromPath('node/123', array('query' => array('foo' => NULL), 'fragment' => 'bar', 'absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");

        $url = $base;
        $result = $this->generator->generateFromPath('<front>', array('absolute' => $absolute));
        $this->assertEquals($url, $result, "$url == $result");
      }
    }
  }

}
