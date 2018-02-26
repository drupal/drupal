<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Utility\Crypt;

/**
 * Tests the installer when a config_directory set up but does not exist.
 *
 * @group Installer
 */
class InstallerConfigDirectorySetNoDirectoryErrorTest extends InstallerTestBase {

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
    $this->settings['config_directories'][CONFIG_SYNC_DIRECTORY] = (object) [
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
  protected function setUpSite() {
    // This step should not appear as we had a failure prior to the settings
    // screen.
  }

  /**
   * Verifies that installation failed.
   */
  public function testError() {
    $this->assertText("An automated attempt to create the directory {$this->configDirectory}/sync failed, possibly due to a permissions problem.");
    $this->assertFalse(file_exists($this->configDirectory . '/sync') && is_dir($this->configDirectory . '/sync'), "The directory {$this->configDirectory}/sync does not exist.");
  }

}
