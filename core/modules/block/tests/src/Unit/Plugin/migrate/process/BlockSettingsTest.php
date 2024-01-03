<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockSettings;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Plugin\migrate\process\BlockSettings
 * @group block
 */
class BlockSettingsTest extends UnitTestCase {

  /**
   * Tests the blocks settings process plugin.
   *
   * @param array $value
   *   The source value for the plugin.
   * @param array $expected
   *   The expected result.
   *
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform($value, $expected) {
    $executable = $this->prophesize(MigrateExecutableInterface::class)
      ->reveal();
    $row = $this->prophesize(Row::class)->reveal();

    $plugin = new BlockSettings([], 'block_settings', []);
    $actual = $plugin->transform($value, $executable, $row, 'foo');
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for testTransform.
   */
  public function providerTestTransform() {
    return [
      'title set' => [
        [
          'custom',
          0,
          'foo',
          'title',
        ],
        [
          'label' => 'title',
          'label_display' => 'visible',
        ],
      ],
      'title empty' => [
        [
          'custom',
          0,
          'foo',
          '',
        ],
        [
          'label' => '',
          'label_display' => '0',
        ],
      ],
      'title <none>' => [
        [
          'custom',
          0,
          'foo',
          '<none>',
        ],
        [
          'label' => '<none>',
          'label_display' => '0',
        ],
      ],
    ];
  }

}
