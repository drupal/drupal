<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Layout\LayoutDefault
 * @group Layout
 */
class LayoutDefaultTest extends UnitTestCase {

  /**
   * @covers ::build
   * @dataProvider providerTestBuild
   */
  public function testBuild($regions, $expected) {
    $definition = new LayoutDefinition([
      'theme_hook' => 'layout',
      'library' => 'core/drupal',
      'regions' => [
        'left' => [
          'label' => 'Left',
        ],
        'right' => [
          'label' => 'Right',
        ],
      ],
    ]);
    $expected += [
      '#in_preview' => FALSE,
      '#settings' => [
        'label' => '',
      ],
      '#layout' => $definition,
      '#theme' => 'layout',
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
    ];

    $layout = new LayoutDefault([], '', $definition);
    $this->assertSame($expected, $layout->build($regions));
  }

  /**
   * Provides test data for ::testBuild().
   */
  public function providerTestBuild() {
    $data = [];
    // Empty regions are not added.
    $data['right_only'] = [
      [
        'right' => [
          'foo' => 'bar',
        ],
      ],
      [
        'right' => [
          'foo' => 'bar',
        ],
      ],
    ];
    // Regions will be in the order defined by the layout.
    $data['switched_order'] = [
      [
        'right' => [
          'foo' => 'bar',
        ],
        'left' => [
          'foo' => 'baz',
        ],
      ],
      [
        'left' => [
          'foo' => 'baz',
        ],
        'right' => [
          'foo' => 'bar',
        ],
      ],
    ];
    return $data;
  }

}
