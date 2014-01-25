<?php
/**
 * @file
 * Contains \Drupal\tour\Tests\Entity\TourTest.
 */

namespace Drupal\tour\Tests\Entity\TourTest;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the Tour entity.
 *
 * @group Tour
 *
 * @coversDefaultClass \Drupal\tour\Entity\Tour
 */
class TourTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tour entity tests',
      'description' => 'Test \Drupal\tour\Entity\Tour.',
      'group' => 'Tour',
    );
  }

  /**
   * Tests \Drupal\tour\Entity\Tour::hasMatchingRoute().
   *
   * @param array $routes
   *   Array of routes as per the Tour::routes property.
   * @param string $route_name
   *   The route name to match.
   * @param array $route_params
   *   Array of route params.
   * @param bool $result
   *   Expected result.
   *
   * @covers ::hasMatchingRoute()
   *
   * @dataProvider routeProvider
   */
  public function testHasMatchingRoute($routes, $route_name, $route_params, $result) {
    $tour = $this->getMockBuilder('\Drupal\tour\Entity\Tour')
      ->disableOriginalConstructor()
      ->setMethods(array('getRoutes'))
      ->getMock();

    $tour->expects($this->any())
      ->method('getRoutes')
      ->will($this->returnValue($routes));

    $this->assertSame($result, $tour->hasMatchingRoute($route_name, $route_params));

    $tour->resetKeyedRoutes();
  }

  /*
   * Provides sample routes for testing.
   */
  public function routeProvider() {
    return array(
      // Simple match.
      array(
        array(
          array('route_name' => 'some.route'),
        ),
        'some.route',
        array(),
        TRUE,
      ),
      // Simple non-match.
      array(
        array(
          array('route_name' => 'another.route'),
        ),
        'some.route',
        array(),
        FALSE,
      ),
      // Empty params.
      array(
        array(
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'bar'),
          ),
        ),
        'some.route',
        array(),
        FALSE,
      ),
      // Match on params.
      array(
        array(
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'bar'),
          ),
        ),
        'some.route',
        array('foo' => 'bar'),
        TRUE,
      ),
      // Non-matching params.
      array(
        array(
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'bar'),
          ),
        ),
        'some.route',
        array('bar' => 'foo'),
        FALSE,
      ),
      // One matching, one not.
      array(
        array(
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'bar'),
          ),
          array(
            'route_name' => 'some.route',
            'route_params' => array('bar' => 'foo'),
          ),
        ),
        'some.route',
        array('bar' => 'foo'),
        TRUE,
      ),
      // One matching, one not.
      array(
        array(
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'bar'),
          ),
          array(
            'route_name' => 'some.route',
            'route_params' => array('foo' => 'baz'),
          ),
        ),
        'some.route',
        array('foo' => 'baz'),
        TRUE,
      ),
    );
  }

}
