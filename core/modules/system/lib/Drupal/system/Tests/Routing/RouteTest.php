<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Routing\RouteTest.
 */

namespace Drupal\system\Tests\Routing;

use Symfony\Component\Routing\Route;

use Drupal\simpletest\UnitTestBase;
use Drupal\Core\Database\Database;

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

  public function testCompilation() {
    $route = new Route('/test/{something}/more');
    $route->setOption('compiler_class', 'Drupal\Core\Routing\RouteCompiler');
    $compiled = $route->compile();

    $this->assertEqual($route, $compiled->getRoute(), t('Compiled route has the correct route object.'));
    $this->assertEqual($compiled->getFit(), 5 /* That's 101 binary*/, t('The fit was correct.'));
    $this->assertEqual($compiled->getPatternOutline(), '/test/%/more', t('The pattern outline was correct.'));
  }

}
