<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Layout\LayoutDefault.
 */
#[CoversClass(LayoutDefault::class)]
#[Group('Layout')]
class LayoutDefaultTest extends UnitTestCase {

  /**
   * Tests build.
   */
  #[DataProvider('providerTestBuild')]
  public function testBuild($regions, $expected): void {
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
  public static function providerTestBuild(): array {
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
