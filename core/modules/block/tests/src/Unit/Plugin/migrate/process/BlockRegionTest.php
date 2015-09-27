<?php

/**
 * @file
 * Contains \Drupal\Tests\block\Unit\Plugin\migrate\process\BlockRegionTest.
 */

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
   * @param \Drupal\migrate\Row|NULL $row
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

    $regions = array(
      'bartik' => array(
        'triptych_first' => 'Triptych first',
        'triptych_second' => 'Triptych second',
        'triptych_third' => 'Triptych third',
      ),
    );
    $plugin = new BlockRegion(['region_map' => []], 'block_region', [], $regions);
    return $plugin->transform($value, $executable, $row, 'foo');
  }

  /**
   * If the source and destination themes are identical, the region should only
   * be passed through if it actually exists in the destination theme.
   *
   * @covers ::transform
   */
  public function testTransformSameThemeRegionExists() {
    $this->assertSame('triptych_second', $this->transform(['triptych_second', 'bartik', 'bartik']));
  }

  /**
   * If the source and destination themes are identical, the region should be
   * changed to 'content' if it doesn't exist in the destination theme.
   *
   * @covers ::transform
   */
  public function testTransformSameThemeRegionNotExists() {
    $this->assertSame('content', $this->transform(['footer', 'bartik', 'bartik']));
  }

}
