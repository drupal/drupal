<?php

namespace Drupal\Tests\hal\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for deleting obsolete Hal settings.
 *
 * @group hal
 * @group legacy
 */
class HalSettingsDeletionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensures that update hook is run for "hal" module.
   */
  public function testUpdate() {

    $hal_settings = $this->config('hal.settings');
    $this->assertFalse($hal_settings->isNew());
    $this->assertTrue($hal_settings->get('bc_file_uri_as_url_normalizer'));

    $this->runUpdates();

    $hal_settings = \Drupal::configFactory()->get('hal.settings');
    $this->assertFalse($hal_settings->isNew());
    $this->assertNull($hal_settings->get('bc_file_uri_as_url_normalizer'));
  }

}
