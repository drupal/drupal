<?php

namespace Drupal\Tests\simpletest\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Verifies that tests in other installation profiles are found.
 *
 * @group simpletest
 * @group legacy
 *
 * @see \Drupal\simpletest\Tests\InstallationProfileModuleTestsTest
 */
class OtherInstallationProfileTestsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simpletest'];

  /**
   * Use the Minimal profile.
   *
   * The Testing profile contains drupal_system_listing_compatible_test.test,
   * which should be found.
   *
   * The Standard profile contains \Drupal\standard\Tests\StandardTest, which
   * should be found.
   *
   * @var string
   *
   * @see \Drupal\simpletest\Tests\InstallationProfileModuleTestsTest
   * @see \Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest
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

    $this->adminUser = $this->drupalCreateUser(['administer unit tests']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that tests located in another installation profile appear.
   *
   * @expectedDeprecation Drupal\simpletest\TestDiscovery is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Test\TestDiscovery instead. See https://www.drupal.org/node/2949692
   */
  public function testOtherInstallationProfile() {
    // Assert the existence of a test in a different installation profile than
    // the current.
    $this->drupalGet(Url::fromRoute('simpletest.test_form'));
    $this->assertText('Tests Standard installation profile expectations.');

    // Assert the existence of a test for a module in a different installation
    // profile than the current.
    $this->assertText('Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest');
  }

}
