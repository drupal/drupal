<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that an install profile can be uninstalled.
 *
 * @group Extension
 */
class InstallProfileUninstallTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * This profile is used because it contains a submodule.
   *
   * @var string
   */
  protected $profile = 'testing_config_import';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests a user can uninstall install profiles.
   */
  public function testUninstallInstallProfile(): void {
    $this->drupalLogin($this->drupalCreateUser(admin: TRUE));

    // Ensure that the installation profile is present on the status report.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains("Installation profile");
    $this->assertSession()->pageTextContains("Testing config import");

    // Test uninstalling a module provided by the install profile.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The install profile 'Testing config import' is providing the following module(s): testing_config_import_module");
    $this->assertSession()->fieldDisabled('uninstall[testing_config_import]');
    $this->assertSession()->fieldEnabled('uninstall[testing_config_import_module]')->check();
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->assertSession()->fieldNotExists('uninstall[testing_config_import_module]');
    $this->assertSession()->pageTextNotContains("The install profile 'Testing config import' is providing the following module(s): testing_config_import_module");

    // Test that we can reinstall the module from the profile.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextContains('Testing config import module');
    $this->assertSession()->fieldEnabled('modules[testing_config_import_module][enable]')->check();
    $this->getSession()->getPage()->pressButton('Install');
    $this->assertSession()->pageTextContains('Module Testing config import module has been installed.');

    // Install a theme provided by the module.
    $this->drupalGet('admin/appearance');
    $this->clickLink("Install Testing config import theme theme");
    $this->assertSession()->pageTextContains("The Testing config import theme theme has been installed.");

    // Test that uninstalling the module and then the profile works.
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextContains("The install profile 'Testing config import' is providing the following module(s): testing_config_import_module");
    $this->assertSession()->pageTextContains("The install profile 'Testing config import' is providing the following theme(s): testing_config_import_theme");
    $this->assertSession()->fieldEnabled('uninstall[testing_config_import_module]')->check();
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->assertSession()->fieldNotExists('uninstall[testing_config_import_module]');
    $this->drupalGet('admin/appearance');
    $this->clickLink("Uninstall Testing config import theme theme");
    $this->assertSession()->pageTextContains("The Testing config import theme theme has been uninstalled.");
    $this->drupalGet('admin/modules/uninstall');
    $this->assertSession()->pageTextNotContains("The install profile 'Testing config import' is providing the following module(s): testing_config_import_module");
    $this->assertSession()->pageTextNotContains("The install profile 'Testing config import' is providing the following theme(s): testing_config_import_theme");
    $this->assertSession()->fieldEnabled('uninstall[testing_config_import]')->check();
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->assertSession()->pageTextContains('Once uninstalled, the Testing config import profile cannot be reinstalled.');
    $this->getSession()->getPage()->pressButton('Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->assertSession()->fieldNotExists('uninstall[testing_config_import]');

    // Test that the module contained in the profile is no longer available to
    // install.
    $this->drupalGet('admin/modules');
    $this->assertSession()->pageTextNotContains('Testing config import module');
    $this->assertSession()->fieldNotExists('modules[testing_config_import_module][enable]');

    // Ensure that the installation profile is not present on the status report.
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains("Installation profile");
    $this->assertSession()->pageTextNotContains("Testing config import");
  }

}
