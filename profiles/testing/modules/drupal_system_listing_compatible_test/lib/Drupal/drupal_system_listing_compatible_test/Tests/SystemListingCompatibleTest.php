<?php

/**
 * Definition of Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest.
 */

namespace Drupal\drupal_system_listing_compatible_test\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Helper to verify tests in installation profile modules.
 */
class SystemListingCompatibleTest extends WebTestBase {
  /**
   * Use the Minimal profile.
   *
   * This test needs to use a different installation profile than the test which
   * asserts that this test is found.
   *
   * @see SimpleTestInstallationProfileModuleTestsTestCase
   */
  protected $profile = 'minimal';

  public static function getInfo() {
    return array(
      'name' => 'Installation profile module tests helper',
      'description' => 'Verifies that tests in installation profile modules are found and may use another profile for running tests.',
      'group' => 'Installation profile',
    );
  }

  function setUp() {
    // Attempt to install a module in Testing profile, while this test runs with
    // a different profile.
    parent::setUp(array('drupal_system_listing_compatible_test'));
  }

  /**
   * Non-empty test* method required to executed the test case class.
   */
  function testSystemListing() {
    $this->pass(__CLASS__ . ' test executed.');
  }
}
