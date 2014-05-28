<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Theme\HtmlAttributesTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for 'html' and 'body' element attributes.
 */
class HtmlAttributesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => "'html' and 'body' element attributes",
      'description' => "Test attributes inserted in the 'html' and 'body' elements on the page.",
      'group' => 'Theme',
    );
  }

  /**
   * Tests that attributes in the 'html' and 'body' elements can be altered.
   */
  function testThemeHtmlAttributes() {
    $this->drupalGet('');
    $attributes = $this->xpath('/html[@theme_test_html_attribute="theme test html attribute value"]');
    $this->assertTrue(count($attributes) == 1, "Attribute set in the 'html' element via hook_preprocess_HOOK() found.");
    $attributes = $this->xpath('/html/body[@theme_test_body_attribute="theme test body attribute value"]');
    $this->assertTrue(count($attributes) == 1, "Attribute set in the 'body' element via hook_preprocess_HOOK() found.");
  }
}
