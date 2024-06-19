<?php

declare(strict_types=1);

namespace Drupal\Tests\path\Unit\migrate\process;

use Drupal\path\Plugin\migrate\process\PathSetTranslated;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the path_set_translated process plugin.
 *
 * @group path
 * @coversDefaultClass \Drupal\path\Plugin\migrate\process\PathSetTranslated
 */
class PathSetTranslatedTest extends MigrateProcessTestCase {

  /**
   * Tests the transform method.
   *
   * @param string $path
   *   The path to test.
   * @param mixed $node_translation
   *   The translated node value to test.
   * @param string $expected_result
   *   The expected result.
   *
   * @covers ::transform
   *
   * @dataProvider transformDataProvider
   */
  public function testTransform($path, $node_translation, $expected_result): void {
    $plugin = new PathSetTranslated([], 'path_set_translated', []);
    $this->assertSame($expected_result, $plugin->transform([$path, $node_translation], $this->migrateExecutable, $this->row, 'destination_property'));
  }

  /**
   * Provides data for the testTransform method.
   *
   * @return array
   *   The data.
   */
  public static function transformDataProvider() {
    return [
      'non-node-path' => [
        'path' => '/non-node-path',
        'node_translation' => [1, 'en'],
        'expected_result' => '/non-node-path',
      ],
      'no_translated_node_1' => [
        'path' => '/node/1',
        'node_translation' => 'INVALID_NID',
        'expected_result' => '/node/1',
      ],
      'no_translated_node_2' => [
        'path' => '/node/1',
        'node_translation' => NULL,
        'expected_result' => '/node/1',
      ],
      'no_translated_node_3' => [
        'path' => '/node/1',
        'node_translation' => FALSE,
        'expected_result' => '/node/1',
      ],
      'valid_transform' => [
        'path' => '/node/1',
        'node_translation' => [3, 'en'],
        'expected_result' => '/node/3',
      ],
    ];
  }

}
