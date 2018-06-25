<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests install with existing settings.php and a mismatching install profile.
 *
 * @group Installer
 * @group legacy
 */
class InstallerExistingSettingsMismatchProfileTest extends InstallerTestBase {

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

    // During interactive install we'll change this to a different profile and
    // this test will ensure that the new value is written to settings.php.
    $this->settings['settings']['install_profile'] = (object) [
      'value' => 'minimal',
      'required' => TRUE,
    ];

    // Pre-configure config directories.
    $this->settings['config_directories'] = [
      CONFIG_SYNC_DIRECTORY => (object) [
        'value' => DrupalKernel::findSitePath(Request::createFromGlobals()) . '/files/config_sync',
        'required' => TRUE,
      ],
    ];
    mkdir($this->settings['config_directories'][CONFIG_SYNC_DIRECTORY]->value, 0777, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function visitInstaller() {
    // Provide profile and language in query string to skip these pages.
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

  /**
   * Verifies that installation succeeded.
   *
   * @expectedDeprecation To access the install profile in Drupal 8 use \Drupal::installProfile() or inject the install_profile container parameter into your service. See https://www.drupal.org/node/2538996
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    $this->assertEqual('testing', \Drupal::installProfile());
    $this->assertEqual('testing', Settings::get('install_profile'), 'Profile was correctly changed to testing in Settings.php');
  }

}
