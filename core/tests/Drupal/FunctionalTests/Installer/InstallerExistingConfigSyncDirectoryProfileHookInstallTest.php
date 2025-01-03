<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that profiles with hook_install() can't be installed from config.
 *
 * @group Installer
 */
class InstallerExistingConfigSyncDirectoryProfileHookInstallTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected $existingSyncDirectory = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller(): void {
    // Create an .install file with a hook_install() implementation.
    $path = $this->siteDirectory . '/profiles/' . $this->profile;
    $contents = <<<EOF
<?php

function testing_config_install_multilingual_install() {
}
EOF;
    file_put_contents("$path/{$this->profile}.install", $contents);
    parent::visitInstaller();
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile(): void {
    // This is the form we are testing so wait until the test method to do
    // assertions.
  }

  /**
   * Installer step: Requirements problem.
   */
  protected function setUpRequirementsProblem(): void {
    // This form will never be reached.
  }

  /**
   * Installer step: Configure settings.
   */
  protected function setUpSettings(): void {
    // This form will never be reached.
  }

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite(): void {
    // This form will never be reached.
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/multilingual';
  }

  /**
   * Tests installing from config is not available due to hook_INSTALL().
   */
  public function testConfigSync(): void {
    $this->assertSession()->titleEquals('Select an installation profile | Drupal');
    $this->assertSession()->responseNotContains('Use existing configuration');

    // Remove the install hook and the option to install from existing
    // configuration will be available.
    unlink("{$this->siteDirectory}/profiles/{$this->profile}/{$this->profile}.install");
    $this->getSession()->reload();
    $this->assertSession()->titleEquals('Select an installation profile | Drupal');
    $this->assertSession()->responseContains('Use existing configuration');
  }

}
