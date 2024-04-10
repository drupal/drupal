<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Asset;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated asset functions.
 *
 * @group Asset
 * @group legacy
 */
class LegacyAssetTest extends KernelTestBase {

  /**
   * Tests the deprecation.
   */
  public function testDeprecatedDrupalFlushCssJs(): void {
    $this->expectDeprecation('_drupal_flush_css_js is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use \Drupal\Core\Asset\AssetQueryStringInterface::reset() instead. See https://www.drupal.org/node/3358337');
    _drupal_flush_css_js();
  }

}
