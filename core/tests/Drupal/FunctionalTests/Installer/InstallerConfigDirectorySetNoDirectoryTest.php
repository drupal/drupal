<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Utility\Crypt;

/**
 * Tests the installer when a custom config directory set up but does not exist.
 *
 * @group Installer
 */
class InstallerConfigDirectorySetNoDirectoryTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  public function testInstaller(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertDirectoryExists($this->syncDirectory);
  }

}
