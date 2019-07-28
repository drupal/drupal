<?php

namespace Drupal\Tests\filter\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the behavior of check_markup() when it is called without text format.
 *
 * @group filter
 */
class FilterNoFormatTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter'];

  /**
   * Tests text without format.
   *
   * Tests if text with no format is filtered the same way as text in the
   * fallback format.
   */
  public function testCheckMarkupNoFormat() {
    $this->installConfig(['filter']);

    // Create some text. Include some HTML and line breaks, so we get a good
    // test of the filtering that is applied to it.
    $text = "<strong>" . $this->randomMachineName(32) . "</strong>\n\n<div>" . $this->randomMachineName(32) . "</div>";

    // Make sure that when this text is run through check_markup() with no text
    // format, it is filtered as though it is in the fallback format.
    $this->assertEquals(check_markup($text), check_markup($text, filter_fallback_format()));
  }

}
