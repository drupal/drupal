<?php

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the installer when a config_directory has already been set up.
 *
 * @group Installer
 */
class InstallerExistingConfigDirectoryTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->settings['config_directories'][CONFIG_SYNC_DIRECTORY] = (object) array(
      'value' => $this->siteDirectory . '/config',
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
  }

}
