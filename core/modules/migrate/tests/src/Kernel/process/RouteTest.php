<?php

namespace Drupal\Tests\migrate\Kernel\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Route;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the route process plugin.
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\migrate\process\Route
 *
 * @group migrate
 */
class RouteTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system'];

  /**
   * Tests Route plugin based on providerTestRoute() values.
   *
   * @param mixed $value
   *   Input value for the Route process plugin.
   * @param array $expected
   *   The expected results from the Route transform process.
   *
   * @dataProvider providerTestRoute
   */
  public function testRoute($value, $expected) {
    $actual = $this->doTransform($value);
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testRoute().
   *
   * @return array
   *   An array of arrays, where the first element is the input to the Route
   *   process plugin, and the second is the expected results.
   */
  public function providerTestRoute() {
    // Internal link tests.
    // Valid link path and options.
    $values[0] = [
      'user/login',
      [
        'attributes' => [
          'title' => 'Test menu link 1',
        ],
      ],
    ];
    $expected[0] = [
      'route_name' => 'user.login',
      'route_parameters' => [],
      'options' => [
        'query' => [],
        'attributes' => [
          'title' => 'Test menu link 1',
        ],
      ],
      'url' => NULL,
    ];

    // Valid link path and empty options.
    $values[1] = [
      'user/login',
      [],
    ];
    $expected[1] = [
      'route_name' => 'user.login',
      'route_parameters' => [],
      'options' => [
        'query' => [],
      ],
      'url' => NULL,
    ];

    // Valid link path and no options.
    $values[2] = 'user/login';
    $expected[2] = [
      'route_name' => 'user.login',
      'route_parameters' => [],
      'options' => [
        'query' => [],
      ],
      'url' => NULL,
    ];

    // Invalid link path.
    $values[3] = 'users';
    $expected[3] = [];

    // Valid link path with parameter.
    $values[4] = [
      'system/timezone/nzdt',
      [
        'attributes' => [
          'title' => 'Show NZDT',
        ],
      ],
    ];
    $expected[4] = [
      'route_name' => 'system.timezone',
      'route_parameters' => [
        'abbreviation' => 'nzdt',
        'offset' => -1,
        'is_daylight_saving_time' => NULL,
      ],
      'options' => [
        'query' => [],
        'attributes' => [
          'title' => 'Show NZDT',
        ],
      ],
      'url' => NULL,
    ];

    // External link tests.
    // Valid external link path and options.
    $values[5] = [
      'https://www.drupal.org',
      [
        'attributes' => [
          'title' => 'Drupal',
        ],
      ],
    ];
    $expected[5] = [
      'route_name' => NULL,
      'route_parameters' => [],
      'options' => [
        'attributes' => [
          'title' => 'Drupal',
        ],
      ],
      'url' => 'https://www.drupal.org',
    ];

    // Valid external link path and options.
    $values[6] = [
      'https://www.drupal.org/user/1/edit?pass-reset-token=QgtDKcRV4e4fjg6v2HTa6CbWx-XzMZ5XBZTufinqsM73qIhscIuU_BjZ6J2tv4dQI6N50ZJOag',
      [
        'attributes' => [
          'title' => 'Drupal password reset',
        ],
      ],
    ];
    $expected[6] = [
      'route_name' => NULL,
      'route_parameters' => [],
      'options' => [
        'attributes' => [
          'title' => 'Drupal password reset',
        ],
      ],
      'url' => 'https://www.drupal.org/user/1/edit?pass-reset-token=QgtDKcRV4e4fjg6v2HTa6CbWx-XzMZ5XBZTufinqsM73qIhscIuU_BjZ6J2tv4dQI6N50ZJOag',
    ];

    return [
      // Test data for internal paths.
      // Test with valid link path and options.
      [$values[0], $expected[0]],
      // Test with valid link path and empty options.
      [$values[1], $expected[1]],
      // Test with valid link path and no options.
      [$values[2], $expected[2]],
      // Test with Invalid link path.
      [$values[3], $expected[3]],
      // Test with Valid link path with query options and parameters.
      [$values[4], $expected[4]],

      // Test data for external paths.
      // Test with external link path and options.
      [$values[5], $expected[5]],
      // Test with valid link path and query options.
      [$values[6], $expected[6]],
    ];
  }

  /**
   * Tests Route plugin based on providerTestRoute() values.
   *
   * @param mixed $value
   *   Input value for the Route process plugin.
   * @param array $expected
   *   The expected results from the Route transform process.
   *
   * @dataProvider providerTestRouteWithParamQuery
   */
  public function testRouteWithParamQuery($value, $expected) {
    // Create a user so that user/1/edit is a valid path.
    $this->setUpCurrentUser();
    $this->installConfig(['user']);

    $actual = $this->doTransform($value);
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testRouteWithParamQuery().
   *
   * @return array
   *   An array of arrays, where the first element is the input to the Route
   *   process plugin, and the second is the expected results.
   */
  public function providerTestRouteWithParamQuery() {
    $values = [];
    $expected = [];
    // Valid link path with query options and parameters.
    $values[0] = [
      'user/1/edit',
      [
        'attributes' => [
          'title' => 'Edit admin',
        ],
        'query' => [
          'destination' => '/admin/people',
        ],
      ],
    ];
    $expected[0] = [
      'route_name' => 'entity.user.edit_form',
      'route_parameters' => [
        'user' => '1',
      ],
      'options' => [
        'attributes' => [
          'title' => 'Edit admin',
        ],
        'query' => [
          'destination' => '/admin/people',
        ],
      ],
      'url' => NULL,
    ];

    return [
      // Test with valid link path with parameters and options.
      [$values[0], $expected[0]],
    ];
  }

  /**
   * Transforms link path data to a route.
   *
   * @param array|string $value
   *   Source link path information.
   *
   * @return array
   *   The route information based on the source link_path.
   */
  protected function doTransform($value) {
    $pathValidator = $this->container->get('path.validator');
    $row = new Row();
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();

    $plugin = new Route([], 'route', [], $migration, $pathValidator);
    $actual = $plugin->transform($value, $executable, $row, 'destination_property');
    return $actual;
  }

}
