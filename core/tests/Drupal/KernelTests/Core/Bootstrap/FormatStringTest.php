<?php

namespace Drupal\KernelTests\Core\Bootstrap;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated behavior of format_string.
 *
 * @group Bootstrap
 * @group legacy
 */
class FormatStringTest extends KernelTestBase {

  /**
   * Tests error triggering on format_string.
   *
   * @expectedDeprecation format_string() is deprecated in drupal:8.0.0 and is removed from drupal:9.0.0. Use \Drupal\Component\Render\FormattableMarkup instead. See https://www.drupal.org/node/2302363
   */
  public function testFormatString() {
    $markup = \format_string("Test", []);
    $this->assertInstanceOf(FormattableMarkup::class, $markup);
  }

}
