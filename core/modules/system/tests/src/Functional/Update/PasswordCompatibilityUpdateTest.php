<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Url;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\user\Entity\User;

/**
 * Tests that users created with Drupal prior to version 10.1.x can still login.
 *
 * @group Update
 */
class PasswordCompatibilityUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-9.4.0.phpass.standard.php.gz',
    ];
  }

  /**
   * Tests that the password compatibility is working properly.
   */
  public function testPasswordCompatibility() {
    $this->runUpdates();

    /** @var \Drupal\Core\Extension\ModuleInstaller $installer */
    $installer = \Drupal::service('module_installer');

    // Log in as user test1 with password "drupal".
    $account1 = User::load(2);
    $account1->passRaw = 'drupal';
    $this->drupalLogin($account1);
    $this->drupalLogout();

    // Uninstall the password compatibility module.
    $installer->uninstall(['phpass']);

    // Log in as user test1 again. The password hash has been updated during the
    // initial login.
    $this->drupalLogin($account1);
    $this->drupalLogout();

    // Attempt to login as user test2 with password "drupal". The password hash
    // is still the one from the database dump.
    $account2 = User::load(3);
    $account2->passRaw = 'drupal';

    $this->drupalGet(Url::fromRoute('user.login'));
    $this->submitForm([
      'name' => $account2->getAccountName(),
      'pass' => $account2->passRaw,
    ], 'Log in');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Unrecognized username or password. Forgot your password?');

    // Reinstall the password compatibility module.
    $installer->install(['phpass']);

    // Attempt to login as user test2 again. This time after the password
    // compatibility module has been reinstalled.
    $this->drupalLogin($account2);
  }

}
