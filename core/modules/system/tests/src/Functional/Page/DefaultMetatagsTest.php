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
    $this->assertEqual(count($result), 1);

    // Ensure that the charset one is the first metatag.
    $result = $this->xpath('//meta');
    $this->assertEqual((string) $result[0]->getAttribute('charset'), 'utf-8');

    // Ensure that the shortcut icon is on the page.
    $result = $this->xpath('//link[@rel = "shortcut icon"]');
    $this->assertEqual(count($result), 1, 'The shortcut icon is present.');
  }

}
