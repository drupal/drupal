<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Bundle\BundleTest.
 */

namespace Drupal\system\Tests\Bundle;

use Drupal\simpletest\WebTestBase;

/**
 * Test bundle registration to the DIC.
 */
class BundleTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Bundle Registration',
      'description' => 'Tests bundle registration to the DIC.',
      'group' => 'Bundle',
    );
  }

  function setUp() {
    parent::setUp('bundle_test');
  }

  /**
   * Test that services provided by module bundles get registered to the DIC.
   */
  function testBundleRegistration() {
    // The page callback at /bundle_test checks
    // drupal_container()->has('bundle_test_class')
    // and if this returns TRUE it outputs a message to this effect. We just
    // need to check that the message appears on the page.
    $this->drupalGet('bundle_test');
    $this->assertText(t('The service with id bundle_test_class is available in the DIC'), t('The bundle_test_class service has been registered to the DIC'));
    // The event subscriber method in the test class calls drupal_set_message with
    // a message saying it has fired. This will fire on every page request so it
    // should show up on the front page.
    $this->drupalGet('');
    $this->assertText(t('The bundle_test event subscriber fired!'), t('The bundle_test event subscriber fired'));
  }
}
