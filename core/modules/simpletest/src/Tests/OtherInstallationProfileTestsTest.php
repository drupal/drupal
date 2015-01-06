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
 * @group simpletest
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

  /**
   * An administrative user with permission to administer unit tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer unit tests'));
    $this->drupalLogin($this->adminUser);
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
    $this->assertText('Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest');
  }

}
