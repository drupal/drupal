<?php

/**
 * @file
 * Contains Drupal\system\Tests\Routing\UrlGeneratorTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;

use Drupal\simpletest\UnitTestBase;

use Drupal\Core\Routing\UrlGenerator;

/**
 * Basic tests for the Route.
 */
class UrlGeneratorTest extends UnitTestBase {

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
    parent::setUp();

    $routes = new RouteCollection();
    $routes->add('test_1', new Route('/test/one'));
    $routes->add('test_2', new Route('/test/two/{narf}'));
    $provider = new MockRouteProvider($routes);

    $this->aliasManager = new MockAliasManager();
    $this->aliasManager->addAlias('test/one', 'hello/world');

    $context = new RequestContext();
    $context->fromRequest(Request::create('/some/path'));

    $generator = new UrlGenerator($provider, $this->aliasManager);
    $generator->setContext($context);

    $this->generator = $generator;
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGeneration() {
    $url = $this->generator->generate('test_1');

    $this->assertEqual($url, '/hello/world', 'Correct URL generated including alias.');
  }

  /**
   * Confirms that generated routes will have aliased paths.
   */
  public function testAliasGenerationWithParameters() {
    $this->aliasManager->addAlias('test/two/5', 'goodbye/cruel/world');

    $url = $this->generator->generate('test_2', array('narf' => '5'));

    $this->assertEqual($url, '/goodbye/cruel/world', 'Correct URL generated including alias and parameters.');
  }

}
