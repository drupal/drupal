<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\RouteCompilerTest.
 */

namespace Drupal\Tests\Core\Routing;

use Symfony\Component\Routing\Route;

use Drupal\Tests\UnitTestCase;

/**
 * Basic tests for the Route.
 *
 * @see \Drupal\Core\Routing\RouteCompiler
 */
class RouteCompilerTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Routes',
      'description' => 'Confirm that route object is functioning properly.',
      'group' => 'Routing',
    );
  }

  /**
   * Confirms that a route compiles properly with the necessary data.
   */
  public function testCompilation() {
    $route = new Route('/test/{something}/more');
    $route->setOption('compiler_class', 'Drupal\Core\Routing\RouteCompiler');
    $compiled = $route->compile();

    $this->assertEquals($route, $compiled->getRoute(), 'Compiled route has the incorrect route object.');
    $this->assertEquals($compiled->getFit(), 5 /* That's 101 binary*/, 'The fit was incorrect.');
    $this->assertEquals($compiled->getPatternOutline(), '/test/%/more', 'The pattern outline was not correct.');
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

    $this->assertEquals($route, $compiled->getRoute(), 'Compiled route has an incorrect route object.');
    $this->assertEquals($compiled->getFit(), 5 /* That's 101 binary*/, 'The fit was not correct.');
    $this->assertEquals($compiled->getPatternOutline(), '/test/%/more', 'The pattern outline was not correct.');
  }

}
