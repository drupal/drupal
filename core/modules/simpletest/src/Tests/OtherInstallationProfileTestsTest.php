<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\OtherInstallationProfileTestsTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verifies that tests in other installation profiles are found.
 *
 * @see SimpleTestInstallationProfileModuleTestsTestCase
 */
class OtherInstallationProfileTestsTest extends WebTestBase {

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
   * which should be found.
   *
   * The Standard profile contains \Drupal\standard\Tests\StandardTest, which
   * should be found.
   *
   * @see \Drupal\simpletest\Tests\InstallationProfileModuleTestsTest
   * @see \Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest
   */
  protected $profile = 'minimal';

  public static function getInfo() {
    return array(
      'name' => 'Other Installation profiles',
      'description' => 'Verifies that tests in modules contained in other installation profiles are found.',
      'group' => 'SimpleTest',
    );
  }

  function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer unit tests'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests that tests located in another installation profile appear.
   */
  function testOtherInstallationProfile() {
    // Assert the existence of a test in a different installation profile than
    // the current.
    $this->drupalGet('admin/config/development/testing');
    $this->assertText('Tests Standard installation profile expectations.');

    // Assert the existence of a test for a module in a different installation
    // profile than the current.
    $this->drupalGet('admin/config/development/testing');
    $this->assertText('Installation profile module tests helper');
  }

}
