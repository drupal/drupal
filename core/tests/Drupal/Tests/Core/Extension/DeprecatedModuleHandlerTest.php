<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ModuleHandler
 * @runTestsInSeparateProcesses
 *
 * @group Extension
 * @group legacy
 */
class DeprecatedModuleHandlerTest extends UnitTestCase {

  /**
   * @dataProvider dependencyProvider
   * @covers ::parseDependency
   * @expectedDeprecation Drupal\Core\Extension\ModuleHandler::parseDependency is deprecated. Use \Drupal\Core\Extension\Dependency::createFromString() instead. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency original_version property is deprecated. Use Drupal\Core\Extension\Dependency::getConstraintString() instead. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Array access to the Drupal\Core\Extension\Dependency versions property is deprecated. See https://www.drupal.org/node/2756875
   * @expectedDeprecation Drupal\Component\Version\Constraint::toArray() only exists to provide a backwards compatibility layer. See https://www.drupal.org/node/2756875
   */
  public function testDependencyParsing($dependency, $expected) {
    $version = ModuleHandler::parseDependency($dependency);
    $this->assertEquals($expected, $version);
  }

  /**
   * Provider for testing dependency parsing.
   */
  public function dependencyProvider() {
    return [
      ['system', ['name' => 'system']],
      ['taxonomy', ['name' => 'taxonomy']],
      ['views', ['name' => 'views']],
      [
        'views_ui(8.x-1.0)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.0)',
          'versions' => [['op' => '=', 'version' => '1.0']],
        ],
      ],
      /* @todo Not supported? Fix this in
         https://www.drupal.org/project/drupal/issues/3001344.
      [
        'views_ui(8.x-1.1-beta)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.1-beta)',
          'versions' => [['op' => '=', 'version' => '1.1-beta']],
        ],
      ],*/
      [
        'views_ui(8.x-1.1-alpha12)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.1-alpha12)',
          'versions' => [['op' => '=', 'version' => '1.1-alpha12']],
        ],
      ],
      [
        'views_ui(8.x-1.1-beta8)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.1-beta8)',
          'versions' => [['op' => '=', 'version' => '1.1-beta8']],
        ],
      ],
      [
        'views_ui(8.x-1.1-rc11)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.1-rc11)',
          'versions' => [['op' => '=', 'version' => '1.1-rc11']],
        ],
      ],
      [
        'views_ui(8.x-1.12)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.12)',
          'versions' => [['op' => '=', 'version' => '1.12']],
        ],
      ],
      [
        'views_ui(8.x-1.x)',
        [
          'name' => 'views_ui',
          'original_version' => ' (8.x-1.x)',
          'versions' => [
            ['op' => '<', 'version' => '2.x'],
            ['op' => '>=', 'version' => '1.x'],
          ],
        ],
      ],
      [
        'views_ui( <= 8.x-1.x)',
        [
          'name' => 'views_ui',
          'original_version' => ' ( <= 8.x-1.x)',
          'versions' => [['op' => '<=', 'version' => '2.x']],
        ],
      ],
      [
        'views_ui(<= 8.x-1.x)',
        [
          'name' => 'views_ui',
          'original_version' => ' (<= 8.x-1.x)',
          'versions' => [['op' => '<=', 'version' => '2.x']],
        ],
      ],
      [
        'views_ui( <=8.x-1.x)',
        [
          'name' => 'views_ui',
          'original_version' => ' ( <=8.x-1.x)',
          'versions' => [['op' => '<=', 'version' => '2.x']],
        ],
      ],
      [
        'views_ui(>8.x-1.x)',
        [
          'name' => 'views_ui',
          'original_version' => ' (>8.x-1.x)',
          'versions' => [['op' => '>', 'version' => '2.x']],
        ],
      ],
      [
        'drupal:views_ui(>8.x-1.x)',
        [
          'project' => 'drupal',
          'name' => 'views_ui',
          'original_version' => ' (>8.x-1.x)',
          'versions' => [['op' => '>', 'version' => '2.x']],
        ],
      ],
    ];
  }

}
