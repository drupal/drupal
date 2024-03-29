<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\filter;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\filter\NumericFilter
 * @group Views
 */
class NumericFilterTest extends UnitTestCase {

  /**
   * Tests the acceptExposedInput method.
   *
   * @covers ::acceptExposedInput
   * @dataProvider provideAcceptExposedInput
   */
  public function testAcceptExposedInput($options, $value, $expected): void {
    $plugin_definition = [
      'title' => $this->randomMachineName(),
    ];

    $plugin = new NumericFilter([], 'numeric', $plugin_definition);
    $translation_stub = $this->getStringTranslationStub();
    $plugin->setStringTranslation($translation_stub);

    $view = $this->prophesize(ViewExecutable::class)->reveal();
    $display = $this->prophesize(DisplayPluginBase::class)->reveal();
    $view->display_handler = $display;
    $plugin->init($view, $view->display_handler, $options);

    $this->assertSame($expected, $plugin->acceptExposedInput($value));
  }

  /**
   * Data provider for testAcceptExposedInput test.
   *
   * @return array[]
   *   The test cases.
   */
  public function provideAcceptExposedInput(): array {
    // [$options, $value, $expected]
    return [
      // Not exposed by default. Bypass parsing and return true.
      'defaults' => [[], [], TRUE],
      'exposed but not configured' => [
        [
          'exposed' => TRUE,
          'expose' => [],
          'group_info' => [],
        ],
        [],
        FALSE,
      ],
      // Exposed but not grouped.
      'exposed not grouped - missing value' => [
        [
          'exposed' => TRUE,
          'expose' => ['identifier' => 'test_id'],
        ],
        [],
        TRUE,
      ],
      'exposed not grouped - wrong group config' => [
        [
          'exposed' => TRUE,
          'group_info' => ['identifier' => 'test_id'],
        ],
        ['test_id' => ['value' => 1]],
        // Wrong identifier configured.
        FALSE,
      ],
      'exposed not grouped' => [
        [
          'exposed' => TRUE,
          'expose' => ['identifier' => 'test_id'],
        ],
        ['test_id' => ['value' => 1]],
        TRUE,
      ],
      // Exposed and grouped.
      'exposed grouped - missing value' => [
        [
          'exposed' => TRUE,
          'is_grouped' => TRUE,
          'group_info' => ['identifier' => 'test_id'],
        ],
        [],
        TRUE,
      ],
      'exposed grouped - wrong group config' => [
        [
          'exposed' => TRUE,
          'is_grouped' => TRUE,
          'expose' => ['identifier' => 'test_id'],
        ],
        ['test_id' => ['value' => 1]],
        FALSE,
      ],
      'exposed grouped' => [
        [
          'exposed' => TRUE,
          'is_grouped' => TRUE,
          'group_info' => ['identifier' => 'test_id'],
        ],
        ['test_id' => ['value' => 1]],
        TRUE,
      ],
    ];
  }

}
