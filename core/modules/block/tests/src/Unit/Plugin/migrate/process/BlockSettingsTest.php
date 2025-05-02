<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockSettings;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

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
  public function testTransform($value, $expected): void {
    $executable = $this->prophesize(MigrateExecutableInterface::class)
      ->reveal();
    $row = $this->prophesize(Row::class)->reveal();

    // The block plugin should be asked to provide default configuration.
    $expected['default'] = 'value';

    $mock_plugin = $this->prophesize(BlockPluginInterface::class);
    $mock_plugin->getConfiguration()
      ->shouldBeCalled()
      ->willReturn($expected);

    $block_manager = $this->prophesize(BlockManagerInterface::class);
    $block_manager->createInstance($value[0], Argument::type('array'))
      ->shouldBeCalled()
      ->willReturn($mock_plugin->reveal());

    $plugin = new BlockSettings([], 'block_settings', [], $block_manager->reveal());
    $actual = $plugin->transform($value, $executable, $row, 'foo');
    $this->assertSame($expected, $actual);
  }

  /**
   * Provides data for testTransform.
   */
  public static function providerTestTransform() {
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
