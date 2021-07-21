<?php

namespace Drupal\FunctionalTests;

use Drupal\Tests\BrowserTestBase;

/**
 * Test legacy user agent functions.
 *
 * @group Test
 * @group legacy
 */
class UserAgentLegacyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test drupal_generate_test_ua() functions.
   */
  public function testDrupalTestUa() {
    $this->expectDeprecation('drupal_generate_test_ua() is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Use \Drupal\Core\Test\UserAgent::generate(). See https://www.drupal.org/node/3044173');
    $test_prefix = drupal_generate_test_ua(drupal_valid_test_ua());
    $this->assertNotEmpty($test_prefix);
    $this->assertNotFalse(drupal_valid_test_ua($test_prefix));
  }

}
