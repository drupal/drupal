<?php

declare(strict_types=1);

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
  public function testMetaTag(): void {
    $this->drupalGet('');
    // Ensures that the charset metatag is on the page.
    $this->assertSession()->elementsCount('xpath', '//meta[@charset="utf-8"]', 1);

    // Ensure that the charset one is the first metatag.
    $result = $this->xpath('//meta');
    $this->assertEquals('utf-8', (string) $result[0]->getAttribute('charset'));

    // Ensure that the icon is on the page.
    $this->assertSession()->elementsCount('xpath', '//link[@rel = "icon"]', 1);
  }

}
