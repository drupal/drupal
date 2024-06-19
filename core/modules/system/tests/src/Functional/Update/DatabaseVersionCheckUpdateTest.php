<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests that updates fail if the database does not meet the minimum version.
 *
 * @group Update
 */
class DatabaseVersionCheckUpdateTest extends BrowserTestBase {
  use UpdatePathTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ensureUpdatesToRun();
  }

  /**
   * Tests that updates fail if the database does not meet the minimum version.
   */
  public function testUpdate(): void {
    if (Database::getConnection()->driver() !== 'mysql') {
      $this->markTestSkipped('This test only works with the mysql driver');
    }

    // Use a database driver that reports a fake database version that does
    // not meet requirements. Only change the necessary settings in the database
    // settings array so that run-tests.sh continues to work.
    $driverExtensionName = 'Drupal\\driver_test\\Driver\\Database\\DrivertestMysqlDeprecatedVersion';
    $autoloading = \Drupal::service('extension.list.database_driver')->get($driverExtensionName)->getAutoloadInfo();
    $settings['databases']['default']['default']['driver'] = (object) [
      'value' => 'DrivertestMysqlDeprecatedVersion',
      'required' => TRUE,
    ];
    $settings['databases']['default']['default']['namespace'] = (object) [
      'value' => $driverExtensionName,
      'required' => TRUE,
    ];
    $settings['databases']['default']['default']['autoload'] = (object) [
      'value' => $autoloading['autoload'],
      'required' => TRUE,
    ];
    $settings['databases']['default']['default']['dependencies'] = (object) [
      'value' => $autoloading['dependencies'],
      'required' => TRUE,
    ];
    $settings['settings'] = [
      'update_free_access' => (object) [
        'value' => TRUE,
        'required' => TRUE,
      ],
    ];
    $this->writeSettings($settings);

    $this->drupalGet(Url::fromRoute('system.db_update'));
    $this->assertSession()->pageTextContains('Errors found');
    $this->assertSession()->pageTextContains('The database server version 10.2.31-MariaDB-1:10.2.31+maria~bionic-log is less than the minimum required version');
  }

}
