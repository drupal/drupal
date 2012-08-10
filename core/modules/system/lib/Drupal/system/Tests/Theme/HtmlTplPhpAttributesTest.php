<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Theme\HtmlTplPhpAttributesTest.
 */

namespace Drupal\system\Tests\Theme;

use Drupal\simpletest\WebTestBase;

/**
 * Functional test for attributes of html.tpl.php.
 */
class HtmlTplPhpAttributesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('theme_test');

  public static function getInfo() {
    return array(
      'name' => 'html.tpl.php html and body attributes',
      'description' => 'Tests attributes inserted in the html and body elements of html.tpl.php.',
      'group' => 'Theme',
    );
  }

  /**
   * Tests that modules and themes can alter variables in html.tpl.php.
   */
  function testThemeHtmlTplPhpAttributes() {
    $this->drupalGet('');
    $attributes = $this->xpath('/html[@theme_test_html_attribute="theme test html attribute value"]');
    $this->assertTrue(count($attributes) == 1, 'Attribute set in the html element via hook_preprocess_HOOK() for html.tpl.php found.');
    $attributes = $this->xpath('/html/body[@theme_test_body_attribute="theme test body attribute value"]');
    $this->assertTrue(count($attributes) == 1, 'Attribute set in the body element via hook_preprocess_HOOK() for html.tpl.php found.');
  }
}
