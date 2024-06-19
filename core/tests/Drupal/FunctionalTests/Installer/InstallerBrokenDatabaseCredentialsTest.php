<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

/**
 * Tests the installer with incorrect connection info in settings.php.
 *
 * @group Installer
 */
class InstallerBrokenDatabaseCredentialsTest extends InstallerTestBase {

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

    // Provide incorrect host name and test the new error messages.
    $connection_info['default']['host'] = 'localhost';

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
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // This form will never be reached.
  }

  /**
   * Tests the expected requirements problem.
   */
  public function testRequirementsProblem(): void {
    $this->assertSession()->titleEquals('Requirements problem | Drupal');
    $this->assertSession()->pageTextContains('Database settings');
    $this->assertSession()->pageTextContains('Resolve all issues below to continue the installation. For help configuring your database server,');
    $this->assertSession()->pageTextContains('[Tip: Drupal was attempting to connect to the database server via a socket, but the socket file could not be found. A Unix socket file is used if you do not specify a host name or if you specify the special host name localhost. To connect via TPC/IP use an IP address (127.0.0.1 for IPv4) instead of "localhost". This message normally means that there is no MySQL server running on the system or that you are using an incorrect Unix socket file name when trying to connect to the server.]');
  }

}
