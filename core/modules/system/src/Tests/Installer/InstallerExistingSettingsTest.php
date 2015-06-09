<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerExistingSettingsTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the installer with an existing settings file.
 *
 * @group Installer
 */
class InstallerExistingSettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   *
   * Fully configures a preexisting settings.php file before invoking the
   * interactive installer.
   */
  protected function setUp() {
    // Pre-configure hash salt.
    // Any string is valid, so simply use the class name of this test.
    $this->settings['settings']['hash_salt'] = (object) array(
      'value' => __CLASS__,
      'required' => TRUE,
    );

    // During interactive install we'll change this to a different profile and
    // this test will ensure that the new value is written to settings.php.
    $this->settings['settings']['install_profile'] = (object) array(
      'value' => 'minimal',
      'required' => TRUE,
    );

    // Pre-configure database credentials.
    $connection_info = Database::getConnectionInfo();
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);

    $this->settings['databases']['default'] = (object) array(
      'value' => $connection_info,
      'required' => TRUE,
    );

    // Use the kernel to find the site path because the site.path service should
    // not be available at this point in the install process.
    $site_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    // Pre-configure config directories.
    $this->settings['config_directories'] = array(
      CONFIG_ACTIVE_DIRECTORY => (object) array(
        'value' => $site_path . '/files/config_active',
        'required' => TRUE,
      ),
      CONFIG_STAGING_DIRECTORY => (object) array(
        'value' => $site_path . '/files/config_staging',
        'required' => TRUE,
      ),
    );
    mkdir($this->settings['config_directories'][CONFIG_ACTIVE_DIRECTORY]->value, 0777, TRUE);
    mkdir($this->settings['config_directories'][CONFIG_STAGING_DIRECTORY]->value, 0777, TRUE);

    parent::setUp();
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
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    $this->assertEqual('testing', drupal_get_profile(), 'Profile was changed from minimal to testing during interactive install.');
  }

}
