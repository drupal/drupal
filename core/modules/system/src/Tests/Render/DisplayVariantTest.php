<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Render\DisplayVariantTest.
 */

namespace Drupal\system\Tests\Render;

use Drupal\simpletest\WebTestBase;

/**
 * Tests selecting a display variant.
 *
 * @group Render
 */
class DisplayVariantTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('display_variant_test');

  /**
   * Tests selecting the variant and passing configuration.
   */
  function testPageDisplayVariantSelectionEvent() {
    // Tests that our display variant was selected, and that its configuration
    // was passed correctly. If the configuration wasn't passed, we'd get an
    // error page here.
    $this->drupalGet('<front>');
    $this->assertRaw('A very important, required value.');
  }

}
