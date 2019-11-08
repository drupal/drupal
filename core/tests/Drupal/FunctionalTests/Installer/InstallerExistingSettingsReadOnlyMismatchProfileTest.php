<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Database\Database;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests installer breaks with a profile mismatch and a read-only settings.php.
 *
 * @group Installer
 * @group legacy
 */
class InstallerExistingSettingsReadOnlyMismatchProfileTest extends InstallerTestBase {

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

    // During interactive install we'll change this to a different profile and
    // this test will ensure that the new value is written to settings.php.
    $this->settings['settings']['install_profile'] = (object) [
      'value' => 'minimal',
      'required' => TRUE,
    ];

    // Pre-configure config directories.
    $site_path = DrupalKernel::findSitePath(Request::createFromGlobals());
    $this->settings['settings']['config_sync_directory'] = (object) [
      'value' => $site_path . '/files/config_staging',
      'required' => TRUE,
    ];
    mkdir($this->settings['settings']['config_sync_directory']->value, 0777, TRUE);
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

  /**
   * Verifies that installation succeeded.
   *
   * @expectedDeprecation To access the install profile in Drupal 8 use \Drupal::installProfile() or inject the install_profile container parameter into your service. See https://www.drupal.org/node/2538996
   */
  public function testInstalled() {
    $this->initBrowserOutputFile();
    $this->htmlOutput(NULL);
    $this->assertEquals('testing', \Drupal::installProfile());
    $this->assertEquals('minimal', Settings::get('install_profile'));
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains("Drupal 8 no longer uses the \$settings['install_profile'] value in settings.php and it can be removed.");
  }

}
