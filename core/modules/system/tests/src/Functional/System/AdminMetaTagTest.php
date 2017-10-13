<?php

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirm that the fingerprinting meta tag appears as expected.
 *
 * @group system
 */
class AdminMetaTagTest extends BrowserTestBase {
  /**
   * Verify that the meta tag HTML is generated correctly.
   */
  public function testMetaTag() {
    list($version,) = explode('.', \Drupal::VERSION);
    $string = '<meta name="Generator" content="Drupal ' . $version . ' (https://www.drupal.org)" />';
    $this->drupalGet('node');
    $this->assertRaw($string, 'Fingerprinting meta tag generated correctly.', 'System');
  }

}
