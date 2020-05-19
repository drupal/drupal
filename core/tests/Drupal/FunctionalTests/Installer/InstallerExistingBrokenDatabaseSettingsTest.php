<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

/**
 * Tests the installer with broken database connection info in settings.php.
 *
 * @group Installer
 */
class InstallerExistingBrokenDatabaseSettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Pre-configure database credentials in settings.php.
    $connection_info = Database::getConnectionInfo();

    if ($connection_info['default']['driver'] !== 'mysql') {
      $this->markTestSkipped('This test relies on overriding the mysql driver');
    }

    // Use a database driver that reports a fake database version that does
    // not meet requirements.
    unset($connection_info['default']['pdo']);
    unset($connection_info['default']['init_commands']);
    $connection_info['default']['driver'] = 'DrivertestMysqlDeprecatedVersion';
    $namespace = 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysqlDeprecatedVersion';
    $connection_info['default']['namespace'] = $namespace;
    $connection_info['default']['autoload'] = Database::findDriverAutoloadDirectory($namespace, \Drupal::root());

    $this->settings['databases']['default'] = (object) [
      'value' => $connection_info,
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings() {
    // This form will never be reached.
    return;
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // This form will never be reached.
    return;
  }

  /**
   * Tests the expected requirements problem.
   */
  public function testRequirementsProblem() {
    $this->assertSession()->titleEquals('Requirements problem | Drupal');
    $this->assertSession()->pageTextContains('Database settings');
    $this->assertSession()->pageTextContains('Resolve all issues below to continue the installation. For help configuring your database server,');
    $this->assertSession()->pageTextContains('The database server version 10.2.31-MariaDB-1:10.2.31+maria~bionic-log is less than the minimum required version');
  }

}
