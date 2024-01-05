<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit\Plugin\migrate\process;

use Drupal\block\Plugin\migrate\process\BlockRegion;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Plugin\migrate\process\BlockRegion
 * @group block
 */
class BlockRegionTest extends UnitTestCase {

  /**
   * Transforms a value through the block_region plugin.
   *
   * @param array $value
   *   The value to transform.
   * @param \Drupal\migrate\Row|null $row
   *   (optional) The mocked row.
   *
   * @return array|string
   *   The transformed value.
   */
  protected function transform(array $value, Row $row = NULL) {
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    if (empty($row)) {
      $row = $this->prophesize(Row::class)->reveal();
    }

    $configuration = [
      'map' => [
        'bartik' => [
          'bartik' => [
            'triptych_first' => 'triptych_first',
            'triptych_middle' => 'triptych_second',
            'triptych_last' => 'triptych_third',
          ],
        ],
      ],
      'default_value' => 'content',
    ];

    $plugin = new BlockRegion($configuration, 'block_region', [], $configuration['map']['bartik']['bartik']);
    return $plugin->transform($value, $executable, $row, 'foo');
  }

  /**
   * Tests transforming a block with the same theme and an existing region.
   *
   * If the source and destination themes are identical, the region should only
   * be passed through if it actually exists in the destination theme.
   *
   * @covers ::transform
   */
  public function testTransformSameThemeRegionExists() {
    $this->assertSame('triptych_second', $this->transform(['bartik', 'bartik', 'triptych_middle']));
  }

  /**
   * Tests transforming a block with the same theme and a non-existent region.
   *
   * If the source and destination themes are identical, the region should be
   * changed to 'content' if it doesn't exist in the destination theme.
   *
   * @covers ::transform
   */
  public function testTransformSameThemeRegionNotExists() {
    $this->assertSame('content', $this->transform(['bartik', 'bartik', 'footer']));
  }

}
