<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Site\Settings;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update to readme inside the configuration synchronization directory.
 *
 * @group Update
 * @group #slow
 */
class ConfigSyncReadmeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests configuration synchronization readme file update.
   */
  public function testConfigurationSynchronizationReadmeUpdate(): void {
    $readme_path = Settings::get('config_sync_directory') . '/README.txt';
    // The test setup does not write the configuration synchronization
    // directory, so let us do it here instead.
    file_put_contents($readme_path, 'Original content had admin/config/development/configuration/sync as path');
    $readme_content = file_get_contents($readme_path);
    $this->assertStringContainsString('admin/config/development/configuration/sync', $readme_content);
    $this->runUpdates();
    $readme_content = file_get_contents($readme_path);
    $this->assertStringNotContainsString('admin/config/development/configuration/sync', $readme_content);
    $this->assertStringContainsString('admin/config/development/configuration', $readme_content);
  }

}
