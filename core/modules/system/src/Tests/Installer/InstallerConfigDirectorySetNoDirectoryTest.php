<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Component\Utility\Crypt;
use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the installer when a config_directory set up but does not exist.
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
  protected function setUp() {
    $this->syncDirectory = $this->publicFilesDirectory . '/config_' . Crypt::randomBytesBase64() . '/sync';
    $this->settings['config_directories'][CONFIG_SYNC_DIRECTORY] = (object) array(
      'value' => $this->syncDirectory,
      'required' => TRUE,
    );
    // Other directories will be created too.
    $this->settings['config_directories']['custom'] = (object) array(
      'value' => $this->publicFilesDirectory . '/config_custom',
      'required' => TRUE,
    );
    parent::setUp();
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    $this->assertTrue(file_exists($this->syncDirectory) && is_dir($this->syncDirectory), "The directory {$this->syncDirectory} exists.");
    $this->assertTrue(file_exists($this->publicFilesDirectory . '/config_custom') && is_dir($this->publicFilesDirectory . '/config_custom'), "The directory {$this->publicFilesDirectory}/custom_config exists.");
  }

}
