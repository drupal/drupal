<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Theme;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests attributes inserted in the 'html' and 'body' elements on the page.
 *
 * @group Theme
 */
class HtmlAttributesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['theme_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that attributes in the 'html' and 'body' elements can be altered.
   */
  public function testThemeHtmlAttributes(): void {
    $this->drupalGet('');
    $this->assertSession()->responseContains('<html lang="en" dir="ltr" theme_test_html_attribute="theme test html attribute value">');
    $this->assertSession()->elementsCount('xpath', '/body[@theme_test_body_attribute="theme test body attribute value"]', 1);
  }

}
