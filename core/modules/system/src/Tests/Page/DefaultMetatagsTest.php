<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\Page\DefaultMetatagsTest.
 */

namespace Drupal\system\Tests\Page;

use Drupal\simpletest\WebTestBase;

/**
 * Tests default HTML metatags on a page.
 *
 * @group Page
 */
class DefaultMetatagsTest extends WebTestBase {

  /**
   * Tests meta tags.
   */
  public function testMetaTag() {
    $this->drupalGet('');
    // Ensures that the charset metatag is on the page.
    $result = $this->xpath('//meta[@name="charset" and @charset="utf-8"]');
    $this->assertEqual(count($result), 1);

    // Ensure that the charset one is the first metatag.
    $result = $this->xpath('//meta');
    $this->assertEqual((string) $result[0]->attributes()->name, 'charset');
    $this->assertEqual((string) $result[0]->attributes()->charset, 'utf-8');
  }

}

