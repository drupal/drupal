<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\AdminMetaTagTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

class AdminMetaTagTest extends WebTestBase {
  /**
   * Implement getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Fingerprinting meta tag',
      'description' => 'Confirm that the fingerprinting meta tag appears as expected.',
      'group' => 'System'
    );
  }

  /**
   * Verify that the meta tag HTML is generated correctly.
   */
  public function testMetaTag() {
    list($version, ) = explode('.', \Drupal::VERSION);
    $string = '<meta name="Generator" content="Drupal ' . $version . ' (http://drupal.org)" />';
    $this->drupalGet('node');
    $this->assertRaw($string, 'Fingerprinting meta tag generated correctly.', 'System');
  }
}
