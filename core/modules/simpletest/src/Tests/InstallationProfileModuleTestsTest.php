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
  public static $modules = ['simpletest'];

  /**
   * An administrative user with permission to administer unit tests.
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
   * @see \Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest
   *
   * @var string
   */
  protected $profile = 'testing';

  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer unit tests']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests existence of test case located in an installation profile module.
   */
  public function testInstallationProfileTests() {
    $this->drupalGet('admin/config/development/testing');
    $this->assertText('Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest');
    $edit = [
      'tests[Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Run tests'));

    // Verifies that tests in installation profile modules are passed.
    $element = $this->xpath('//tr[contains(@class, :class)]/td[contains(text(), :value)]', [
      ':class' => 'simpletest-pass',
      ':value' => 'Drupal\Tests\drupal_system_listing_compatible_test\Kernel\SystemListingCrossProfileCompatibleTest',
    ]);
    $this->assertTrue(!empty($element));
  }

}
