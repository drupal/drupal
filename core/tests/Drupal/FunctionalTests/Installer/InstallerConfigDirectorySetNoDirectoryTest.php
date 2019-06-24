<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Utility\Crypt;

/**
 * Tests the installer when a custom config directory set up but does not exist.
 *
 * @group Installer
 */
class InstallerConfigDirectorySetNoDirectoryTest extends InstallerTestBase {

  /**
   * The sync directory created during the install.
   *
   * @var string
   */
  protected $syncDirectory;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $this->syncDirectory = $this->publicFilesDirectory . '/config_' . Crypt::randomBytesBase64() . '/sync';
    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => $this->syncDirectory,
      'required' => TRUE,
    ];
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    $this->assertTrue(file_exists($this->syncDirectory) && is_dir($this->syncDirectory), "The directory {$this->syncDirectory} exists.");
  }

}
