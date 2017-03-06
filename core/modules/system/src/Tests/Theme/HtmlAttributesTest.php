<?php

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Tests attributes inserted in the 'html' and 'body' elements on the page.
 *
 * @group Theme
 */
class HtmlAttributesTest extends WebTestBase {

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
    $attributes = $this->xpath('/html[@theme_test_html_attribute="theme test html attribute value"]');
    $this->assertTrue(count($attributes) == 1, "Attribute set in the 'html' element via hook_preprocess_HOOK() found.");
    $attributes = $this->xpath('/html/body[@theme_test_body_attribute="theme test body attribute value"]');
    $this->assertTrue(count($attributes) == 1, "Attribute set in the 'body' element via hook_preprocess_HOOK() found.");
  }

}
