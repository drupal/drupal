<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests re-visiting the installer after a successful installation.
 *
 * @group Installer
 */
class InstallerPostInstallTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Confirms that visiting the installer does not break things post-install.
   */
  public function testVisitInstallerPostInstall() {
    \Drupal::service('module_installer')->install(['system_test']);
    // Clear caches to ensure that system_test's routes are available.
    $this->resetAll();
    // Confirm that the install_profile is correct.
    $this->drupalGet('/system-test/get-install-profile');
    $this->assertSession()->pageTextContains('minimal');
    // Make an anonymous visit to the installer
    $this->drupalLogout();
    $this->visitInstaller();
    // Ensure that the install profile is still correct.
    $this->drupalGet('/system-test/get-install-profile');
    $this->assertSession()->pageTextContains('minimal');
  }

}
