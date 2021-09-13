<?php

namespace Drupal\Tests\tour\Unit\Entity;

use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\tour\Entity\Tour
 * @group tour
 */
class TourTest extends UnitTestCase {

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
   * @covers ::hasMatchingRoute
   *
   * @dataProvider routeProvider
   */
  public function testHasMatchingRoute($routes, $route_name, $route_params, $result) {
    $tour = $this->getMockBuilder('\Drupal\tour\Entity\Tour')
      ->disableOriginalConstructor()
      ->onlyMethods(['getRoutes'])
      ->getMock();

    $tour->expects($this->any())
      ->method('getRoutes')
      ->will($this->returnValue($routes));

    $this->assertSame($result, $tour->hasMatchingRoute($route_name, $route_params));

    $tour->resetKeyedRoutes();
  }

  /**
   * Provides sample routes for testing.
   */
  public function routeProvider() {
    return [
      // Simple match.
      [
        [
          ['route_name' => 'some.route'],
        ],
        'some.route',
        [],
        TRUE,
      ],
      // Simple non-match.
      [
        [
          ['route_name' => 'another.route'],
        ],
        'some.route',
        [],
        FALSE,
      ],
      // Empty params.
      [
        [
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'bar'],
          ],
        ],
        'some.route',
        [],
        FALSE,
      ],
      // Match on params.
      [
        [
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'bar'],
          ],
        ],
        'some.route',
        ['foo' => 'bar'],
        TRUE,
      ],
      // Non-matching params.
      [
        [
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'bar'],
          ],
        ],
        'some.route',
        ['bar' => 'foo'],
        FALSE,
      ],
      // One matching, one not.
      [
        [
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'bar'],
          ],
          [
            'route_name' => 'some.route',
            'route_params' => ['bar' => 'foo'],
          ],
        ],
        'some.route',
        ['bar' => 'foo'],
        TRUE,
      ],
      // One matching, one not.
      [
        [
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'bar'],
          ],
          [
            'route_name' => 'some.route',
            'route_params' => ['foo' => 'baz'],
          ],
        ],
        'some.route',
        ['foo' => 'baz'],
        TRUE,
      ],
    ];
  }

}
