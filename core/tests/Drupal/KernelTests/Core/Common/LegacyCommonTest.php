<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated legacy functions in common.inc.
 *
 * @group Common
 * @group legacy
 */
class LegacyCommonTest extends KernelTestBase {

  /**
   * Tests deprecation of the format_size() function.
   */
  public function testFormatSizeDeprecation(): void {
    $this->expectDeprecation('format_size() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use \Drupal\Core\StringTranslation\ByteSizeMarkup::create($size, $langcode) instead. See https://www.drupal.org/node/2999981');
    $size = format_size(4053371676);
    $this->assertEquals('3.77 GB', $size);
    $this->assertEquals('@size GB', $size->getUntranslatedString());
  }

}
