<?php

namespace Drupal\Tests\system\Functional\Page;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests default HTML metatags on a page.
 *
 * @group Page
 */
class DefaultMetatagsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests meta tags.
   */
  public function testMetaTag() {
    $this->drupalGet('');
    // Ensures that the charset metatag is on the page.
    $result = $this->xpath('//meta[@charset="utf-8"]');
    $this->assertCount(1, $result);

    // Ensure that the charset one is the first metatag.
    $result = $this->xpath('//meta');
    $this->assertEquals('utf-8', (string) $result[0]->getAttribute('charset'));

    // Ensure that the icon is on the page.
    $result = $this->xpath('//link[@rel = "icon"]');
    $this->assertCount(1, $result, 'The icon is present.');
  }

}
