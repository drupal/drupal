<?php

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests attributes inserted in the 'html' and 'body' elements on the page.
 *
 * @group Theme
 */
class HtmlAttributesTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['theme_test'];

  /**
   * Tests that attributes in the 'html' and 'body' elements can be altered.
   */
  public function testThemeHtmlAttributes() {
    $this->drupalGet('');
    $this->assertSession()->responseContains('<html lang="en" dir="ltr" theme_test_html_attribute="theme test html attribute value">');
    $attributes = $this->xpath('/body[@theme_test_body_attribute="theme test body attribute value"]');
    $this->assertTrue(count($attributes) == 1, "Attribute set in the 'body' element via hook_preprocess_HOOK() found.");
  }

}
