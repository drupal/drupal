<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for removal the system.authorize configuration.
 *
 * @see https://www.drupal.org/node/3206320
 * @see system_post_update_delete_authorize_settings()
 *
 * @group Update
 */
class DeleteAuthorizeSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_post_update_delete_authorize_settings().
   */
  public function testSystemAuthorizeRemoval() {
    $this->assertArrayHasKey('filetransfer_default', $this->config('system.authorize')->getRawData());

    $this->runUpdates();

    $config = $this->config('system.authorize');
    $this->assertTrue($config->isNew());
  }

}
