<?php

namespace Drupal\filter\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the behavior of check_markup() when it is called without text format.
 *
 * @group filter
 */
class FilterNoFormatTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('filter');

  /**
   * Tests text without format.
   *
   * Tests if text with no format is filtered the same way as text in the
   * fallback format.
   */
  function testCheckMarkupNoFormat() {
    // Create some text. Include some HTML and line breaks, so we get a good
    // test of the filtering that is applied to it.
    $text = "<strong>" . $this->randomMachineName(32) . "</strong>\n\n<div>" . $this->randomMachineName(32) . "</div>";

    // Make sure that when this text is run through check_markup() with no text
    // format, it is filtered as though it is in the fallback format.
    $this->assertEqual(check_markup($text), check_markup($text, filter_fallback_format()), 'Text with no format is filtered the same as text in the fallback format.');
  }

}
