<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests installation when a config_sync_directory exists and is set up.
 *
 * @group Installer
 */
class InstallerExistingConfigDirectoryTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The expected file perms of the folder.
   *
   * @var int
   */
  protected $expectedFilePerms;

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    mkdir($this->root . DIRECTORY_SEPARATOR . $this->siteDirectory . '/config_read_only', 0444);
    $this->expectedFilePerms = fileperms($this->siteDirectory . '/config_read_only');
    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => $this->siteDirectory . '/config_read_only',
      'required' => TRUE,
    ];
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller(): void {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals($this->expectedFilePerms, fileperms($this->siteDirectory . '/config_read_only'));
    $this->assertEquals([], glob($this->siteDirectory . '/config_read_only/*'), 'The sync directory is empty after install because it is read-only.');
  }

}
