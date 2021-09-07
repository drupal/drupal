<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Tests the interactive installer with deprecated table prefix array.
 *
 * @group Installer
 */
class InstallerWithTablePrefixArrayTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that the status report raises the warning after installation.
   */
  public function testInstall(): void {
    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextNotContains("There is at least one database entry in the \$database array in settings.php that has a 'prefix' value in the format of an array. Per-table prefixes are no longer supported.");

    // Add a database with a multi-entry 'prefix' array.
    $settings['databases']['test_fu']['default'] = (object) [
      'value' => [
        'database' => 'drupal_db',
        'prefix' => ['default' => 'foo', 'other_table' => 'qux'],
        'host' => 'localhost',
        'namespace' => 'Drupal\Core\Database\Driver\sqlite',
        'driver' => 'sqlite',
      ],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->pageTextContains("There is at least one database entry in the \$database array in settings.php that has a 'prefix' value in the format of an array. Per-table prefixes are no longer supported.");
  }

}
