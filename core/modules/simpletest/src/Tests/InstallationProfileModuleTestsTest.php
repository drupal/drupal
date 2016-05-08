<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Verifies that tests bundled with installation profile modules are found.
 *
 * @group simpletest
 */
class InstallationProfileModuleTestsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  /**
   * An administrative user with permission to adminsiter unit tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Use the Testing profile.
   *
   * The Testing profile contains drupal_system_listing_compatible_test.test,
   * which attempts to:
   * - run tests using the Minimal profile (which does not contain the
   *   drupal_system_listing_compatible_test.module)
   * - but still install the drupal_system_listing_compatible_test.module
   *   contained in the Testing profile.
   *
   * @see \Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest
   */
  protected $profile = 'testing';

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(array('administer unit tests'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests existence of test case located in an installation profile module.
   */
  function testInstallationProfileTests() {
    $this->drupalGet('admin/config/development/testing');
    $this->assertText('Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest');
    $edit = array(
      'tests[Drupal\drupal_system_listing_compatible_test\Tests\SystemListingCompatibleTest]' => TRUE,
    );
    $this->drupalPostForm(NULL, $edit, t('Run tests'));
    $this->assertText('SystemListingCompatibleTest test executed.');
  }

}
