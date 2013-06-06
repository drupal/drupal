<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\UrlGeneratorTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Component\Utility\Settings;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\Context\ConfigContextFactory;
use Drupal\Core\PathProcessor\PathProcessorAlias;
use Drupal\Core\PathProcessor\PathProcessorManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;

use Drupal\Tests\UnitTestCase;

use Drupal\Core\Routing\UrlGenerator;

/**
 * Basic tests for the Route.
 *
 * @group Routing
 */
class UrlGeneratorTest extends UnitTestCase {

  protected $generator;

  protected $aliasManager;

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
    $routes->add('test_1', $first_route);
    $routes->add('test_2', $second_route);

    // Create a route provider stub.
    $provider = $this->getMockBuilder('Drupal\Core\Routing\RouteProvider')
      ->disableOriginalConstructor()
      ->getMock();
    $route_name_return_map = array(
      array('test_1', array(), $first_route),
      array('test_2', array('narf' => '5'), $second_route),
    );
    $provider->expects($this->any())
      ->method('getRouteByName')
      ->will($this->returnValueMap($route_name_return_map));
    $routes_names_return_map = array(
      array(array('test_1'), array(), array($first_route)),
      array(array('test_2'), array('narf' => '5'), array($second_route)),
    );
    $provider->expects($this->any())
      ->method('getRoutesByNames')
      ->will($this->returnValueMap($routes_names_return_map));

    // Create an alias manager stub.
    $alias_manager = $this->getMockBuilder('Drupal\Core\Path\AliasManager')
      ->disableOriginalConstructor()
      ->getMock();
    $alias_map = array(
      array('test/one', NULL, 'hello/world'),
      array('test/two/5', NULL, 'goodbye/cruel/world'),
      array('node/123', NULL, 'node/123'),
    );
    $alias_manager->expects($this->any())
      ->method('getPathAlias')
      ->will($this->returnValueMap($alias_map));

    $this->aliasManager = $alias_manager;

    $context = new RequestContext();
    $context->fromRequest(Request::create('/some/path'));

    $processor = new PathProcessorAlias($this->aliasManager);
    $processor_manager = new PathProcessorManager();
    $processor_manager->addOutbound($processor, 1000);

    $config_factory_stub = $this->getConfigFactoryStub(array('system.filter' => array('protocols' => array('http', 'https'))));

    $generator = new UrlGenerator($provider, $processor_manager, $config_factory_stub, new Settings(array()));
    $generator->setContext($context);

    $this->generator = $generator;
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGeneration() {
    $url = $this->generator->generate('test_1');
    $this->assertEquals('/hello/world', $url);
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGenerationWithParameters() {
    $url = $this->generator->generate('test_2', array('narf' => '5'));
    $this->assertEquals('/goodbye/cruel/world', $url, 'Correct URL generated including alias and parameters.');
  }

  /**
   * Confirms that absolute URLs work with generated routes.
   */
  public function testAbsoluteURLGeneration() {
    $url = $this->generator->generate('test_1', array(), TRUE);
    $this->assertEquals('http://localhost/hello/world', $url);
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
        $absolute_string = $absolute ? 'absolute' : NULL;
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
