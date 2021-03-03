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
    $this->assertEqual('utf-8', (string) $result[0]->getAttribute('charset'));

    // Ensure that the shortcut icon is on the page.
    $result = $this->xpath('//link[@rel = "shortcut icon"]');
    $this->assertCount(1, $result, 'The shortcut icon is present.');
  }

}
