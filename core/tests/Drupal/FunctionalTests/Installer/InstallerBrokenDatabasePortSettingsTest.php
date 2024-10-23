<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Database\Database;

/**
 * Tests the installer with incorrect connection info in settings.php.
 *
 * @group Installer
 */
class InstallerBrokenDatabasePortSettingsTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    // Pre-configure database credentials in settings.php.
    $connection_info = Database::getConnectionInfo();

    if ($connection_info['default']['driver'] !== 'mysql') {
      $this->markTestSkipped('This test relies on overriding the mysql driver');
    }

    // Override the port number to random port.
    $connection_info['default']['port'] = 1234;

    $this->settings['databases']['default'] = (object) [
      'value' => $connection_info,
      'required' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSettings(): void {
    // This form will never be reached.
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    // This form will never be reached.
  }

  /**
   * Tests the expected requirements problem.
   */
  public function testRequirementsProblem(): void {
    $this->assertSession()->titleEquals('Requirements problem | Drupal');
    $this->assertSession()->pageTextContains('Database settings');
    $this->assertSession()->pageTextContains('Resolve all issues below to continue the installation. For help configuring your database server,');
    $this->assertSession()->pageTextContains("[Tip: This message normally means that there is no MySQL server running on the system or that you are using an incorrect host name or port number when trying to connect to the server. You should also check that the TCP/IP port you are using has not been blocked by a firewall or port blocking service.]");
  }

}
