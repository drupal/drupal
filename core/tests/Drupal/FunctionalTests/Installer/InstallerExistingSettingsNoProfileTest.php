<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the installer with an existing settings file but no install profile.
 *
 * @group Installer
 */
class InstallerExistingSettingsNoProfileTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * Configures a preexisting settings.php file without an install_profile
   * setting before invoking the interactive installer.
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();

    // Pre-configure hash salt.
    // Any string is valid, so simply use the class name of this test.
    $this->settings['settings']['hash_salt'] = (object) [
      'value' => __CLASS__,
      'required' => TRUE,
    ];

    // Pre-configure database credentials.
    $connection_info = Database::getConnectionInfo();
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);

    $this->settings['databases']['default'] = (object) [
      'value' => $connection_info,
      'required' => TRUE,
    ];

    // Pre-configure config directories.
    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => DrupalKernel::findSitePath(Request::createFromGlobals()) . '/files/config_sync',
      'required' => TRUE,
    ];
    mkdir($this->settings['settings']['config_sync_directory']->value, 0777, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // This step should not appear, since settings.php is fully configured
    // already.
  }

  /**
   * Verifies that installation succeeded.
   */
  public function testInstaller() {
    $this->assertSession()->addressEquals('user/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEqual('testing', \Drupal::installProfile());
  }

}
