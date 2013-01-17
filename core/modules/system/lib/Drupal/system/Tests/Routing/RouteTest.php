<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\RouteTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\Routing\Route;

use Drupal\simpletest\UnitTestBase;

/**
 * Basic tests for the Route.
 */
class RouteTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Routes',
      'description' => 'Confirm that route object is functioning properly.',
      'group' => 'Routing',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Confirms that a route compiles properly with the necessary data.
   */
  public function testCompilation() {
    $route = new Route('/test/{something}/more');
    $route->setOption('compiler_class', 'Drupal\Core\Routing\RouteCompiler');
    $compiled = $route->compile();

    $this->assertEqual($route, $compiled->getRoute(), 'Compiled route has the correct route object.');
    $this->assertEqual($compiled->getFit(), 5 /* That's 101 binary*/, 'The fit was correct.');
    $this->assertEqual($compiled->getPatternOutline(), '/test/%/more', 'The pattern outline was correct.');
  }

  /**
   * Confirms that a compiled route with default values has the correct outline.
   */
  public function testCompilationDefaultValue() {
    // Because "here" has a default value, it should not factor into the outline
    // or the fitness.
    $route = new Route('/test/{something}/more/{here}', array(
      'here' => 'there',
    ));
    $route->setOption('compiler_class', 'Drupal\Core\Routing\RouteCompiler');
    $compiled = $route->compile();

    $this->assertEqual($route, $compiled->getRoute(), 'Compiled route has the correct route object.');
    $this->assertEqual($compiled->getFit(), 5 /* That's 101 binary*/, 'The fit was correct.');
    $this->assertEqual($compiled->getPatternOutline(), '/test/%/more', 'The pattern outline was correct.');
  }

}
