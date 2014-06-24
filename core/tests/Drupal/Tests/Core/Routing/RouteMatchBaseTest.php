<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\RouteMatchTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Base test class for testing the RouteMatch object.
 */
abstract class RouteMatchBaseTest extends UnitTestCase {

  /**
   * Build a test route match object for the given implementation.
   *
   * @param $name
   *   Route name.
   * @param Route $route
   *   Request object
   * @param array $parameters
   *   Parameters array
   * @param $raw_parameters
   *   Raw parameters array
   * @return \Drupal\Core\Routing\RouteMatchInterface
   */
  abstract protected function getRouteMatch($name, Route $route, array $parameters, array $raw_parameters);


  /**
   * Provide sets of parameters and expected parameters for parameter tests.
   */
  public function routeMatchProvider() {
    $base_data = array(
      array(
        new Route(
          '/test-route/{param_without_leading_underscore}/{_param_with_leading_underscore}',
          array(
            'default_without_leading_underscore' => NULL,
            '_default_with_leading_underscore' => NULL,
          )
        ),
        array(
          'param_without_leading_underscore' => 'value',
          '_param_with_leading_underscore' => 'value',
          'default_without_leading_underscore' => 'value',
          '_default_with_leading_underscore' => 'value',
          'foo' => 'value',
        ),
        // Parameters should be filtered to only those defined by the route.
        // Specifically:
        // - Path parameters, regardless of name.
        // - Defaults that are not path parameters only if they do not start with
        //   an underscore.
        array(
          'param_without_leading_underscore' => 'value',
          '_param_with_leading_underscore' => 'value',
          'default_without_leading_underscore' => 'value',
        ),
      ),
    );

    $data = array();
    foreach ($base_data as $entry) {
      $route = $entry[0];
      $params = $entry[1];
      $expected_params = $entry[2];
      $data[] = array(
        $this->getRouteMatch('test_route', $route, $params, $params),
        $route,
        $params,
        $expected_params,
      );
    }

    return $data;
  }

  /**
   * @covers ::getRouteName
   * @dataProvider routeMatchProvider
   */
  public function testGetRouteName(RouteMatchInterface $route_match) {
    $this->assertSame('test_route', $route_match->getRouteName());
  }

  /**
   * @covers ::getRouteObject
   * @dataProvider routeMatchProvider
   */
  public function testGetRouteObject(RouteMatchInterface $route_match, Route $route) {
    $this->assertSame($route, $route_match->getRouteObject());
  }

  /**
   * @covers ::getParameter
   * @covers \Drupal\Core\Routing\RouteMatch::getParameterNames
   * @dataProvider routeMatchProvider
   */
  public function testGetParameter(RouteMatchInterface $route_match, Route $route, $parameters, $expected_filtered_parameters) {
    foreach ($expected_filtered_parameters as $name => $expected_value) {
      $this->assertSame($expected_value, $route_match->getParameter($name));
    }
    foreach (array_diff_key($parameters, $expected_filtered_parameters) as $name) {
      $this->assertNull($route_match->getParameter($name));
    }
  }

  /**
   * @covers ::getParameters
   * @covers \Drupal\Core\Routing\RouteMatch::getParameterNames
   * @dataProvider routeMatchProvider
   */
  public function testGetParameters(RouteMatchInterface $route_match, Route $route, $parameters, $expected_filtered_parameters) {
    $this->assertSame($expected_filtered_parameters, $route_match->getParameters()->all());
  }

  /**
   * @covers ::getRawParameter
   * @covers \Drupal\Core\Routing\RouteMatch::getParameterNames
   * @dataProvider routeMatchProvider
   */
  public function testGetRawParameter(RouteMatchInterface $route_match, Route $route, $parameters, $expected_filtered_parameters) {
    foreach ($expected_filtered_parameters as $name => $expected_value) {
      $this->assertSame($expected_value, $route_match->getRawParameter($name));
    }
    foreach (array_diff_key($parameters, $expected_filtered_parameters) as $name) {
      $this->assertNull($route_match->getRawParameter($name));
    }
  }

  /**
   * @covers ::getRawParameters
   * @covers \Drupal\Core\Routing\RouteMatch::getParameterNames
   * @dataProvider routeMatchProvider
   */
  public function testGetRawParameters(RouteMatchInterface $route_match, Route $route, $parameters, $expected_filtered_parameters) {
    $this->assertSame($expected_filtered_parameters, $route_match->getRawParameters()->all());
  }

}
