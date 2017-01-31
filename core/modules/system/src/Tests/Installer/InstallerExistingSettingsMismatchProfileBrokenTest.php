<?php

namespace Drupal\system\Tests\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\simpletest\InstallerTestBase;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests installer breaks with a profile mismatch and a read-only settings.php.
 *
 * @group Installer
 */
class InstallerExistingSettingsMismatchProfileBrokenTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   *
   * Configures a preexisting settings.php file without an install_profile
   * setting before invoking the interactive installer.
   */
  protected function setUp() {
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

    // During interactive install we'll change this to a different profile and
    // this test will ensure that the new value is written to settings.php.
    $this->settings['settings']['install_profile'] = (object) [
      'value' => 'minimal',
      'required' => TRUE,
    ];

    // Pre-configure config directories.
    $site_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $this->settings['config_directories'] = [
      CONFIG_SYNC_DIRECTORY => (object) [
        'value' => $site_path . '/files/config_staging',
        'required' => TRUE,
      ],
    ];
    mkdir($this->settings['config_directories'][CONFIG_SYNC_DIRECTORY]->value, 0777, TRUE);

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    // Make settings file not writable. This will break the installer.
    $filename = $this->siteDirectory . '/settings.php';
    // Make the settings file read-only.
    // Not using File API; a potential error must trigger a PHP warning.
    chmod($filename, 0444);

    $this->drupalGet($GLOBALS['base_url'] . '/core/install.php?langcode=en&profile=testing');
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    // This step is skipped, because there is a lagcode as a query param.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a profile as a query param.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // This step should not appear, since settings.php is fully configured
    // already.
  }

  protected function setUpSite() {
    // This step should not appear, since settings.php could not be written.
  }

  /**
   * Verifies that installation did not succeed.
   */
  public function testBrokenInstaller() {
    $this->assertTitle('Install profile mismatch | Drupal');
    $this->assertText("The selected profile testing does not match the install_profile setting, which is minimal. Cannot write updated setting to {$this->siteDirectory}/settings.php.");
  }

}
