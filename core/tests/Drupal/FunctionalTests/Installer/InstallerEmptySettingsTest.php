<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests the installer with empty settings file.
 *
 * @group Installer
 */
class InstallerEmptySettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Create an empty settings.php file.
    $path = $this->root . DIRECTORY_SEPARATOR . $this->siteDirectory;
    file_put_contents($path . '/settings.php', '');
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertSession()->statusCodeEquals(200);
  }

}
