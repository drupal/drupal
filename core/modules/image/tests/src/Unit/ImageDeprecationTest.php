<?php

declare(strict_types=1);

namespace Drupal\Tests\image\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests deprecation of image_filter_keyword().
 *
 * @group Image
 * @group legacy
 */
class ImageDeprecationTest extends UnitTestCase {

  /**
   * Tests deprecation of image_filter_keyword.
   */
  public function testImageFilterKeywordDeprecation(): void {
    include_once __DIR__ . '/../../../image.module';
    $this->expectDeprecation('image_filter_keyword() is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use \Drupal\Component\Utility\Image::getKeywordOffset() instead. See https://www.drupal.org/node/3268441');
    $this->assertSame('miss', image_filter_keyword('miss', 0, 0));
  }

}
