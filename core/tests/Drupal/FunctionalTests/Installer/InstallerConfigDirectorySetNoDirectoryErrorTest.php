<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Utility\Crypt;

/**
 * Tests installation when a config_sync_directory is set up but does not exist.
 *
 * @group Installer
 */
class InstallerConfigDirectorySetNoDirectoryErrorTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The directory where the sync directory should be created during install.
   *
   * @var string
   */
  protected $configDirectory;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $this->configDirectory = $this->publicFilesDirectory . '/config_' . Crypt::randomBytesBase64();
    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => $this->configDirectory . '/sync',
      'required' => TRUE,
    ];
    // Create the files directory early so we can test the error case.
    mkdir($this->publicFilesDirectory);
    // Create a file so the directory can not be created.
    file_put_contents($this->configDirectory, 'Test');
  }

  /**
   * Installer step: Configure settings.
   */
  protected function setUpSettings() {
    // This step should not appear as we had a failure prior to the settings
    // screen.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpRequirementsProblem() {
    // The parent method asserts that there are no requirements errors, but
    // this test expects a requirements error in the test method below.
    // Therefore, we override this method to suppress the parent's assertions.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // This step should not appear as we had a failure prior to the settings
    // screen.
  }

  /**
   * Verifies that installation failed.
   */
  public function testError(): void {
    $this->assertSession()->pageTextContains("An automated attempt to create the directory {$this->configDirectory}/sync failed, possibly due to a permissions problem.");
    $this->assertDirectoryDoesNotExist($this->configDirectory . '/sync');
  }

}
