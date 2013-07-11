<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\OtherInstallationProfileModuleTestsTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verifies that tests in other installation profiles are not found.
 *
 * @see SimpleTestInstallationProfileModuleTestsTestCase
 */
class OtherInstallationProfileModuleTestsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  /**
   * Use the Minimal profile.
   *
   * The Testing profile contains drupal_system_listing_compatible_test.test,
   * which should not be found.
   *
   * @see SimpleTestInstallationProfileModuleTestsTestCase
   * @see \Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest
   */
  protected $profile = 'minimal';

  public static function getInfo() {
    return array(
      'name' => 'Other Installation profiles',
      'description' => 'Verifies that tests in other installation profiles are not found.',
      'group' => 'SimpleTest',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer unit tests'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests that tests located in another installation profile do not appear.
   */
  function testOtherInstallationProfile() {
    $this->drupalGet('admin/config/development/testing');
    $this->assertNoText('Installation profile module tests helper');
  }
}
