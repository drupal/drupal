<?php

namespace Drupal\Tests\rest\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for deleting obsolete REST settings.
 *
 * @group legacy
 */
class RestSettingsDeletionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensures that update hook is run for "rest" module.
   */
  public function testUpdate() {
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse($rest_settings->isNew());

    $this->runUpdates();

    $rest_settings = \Drupal::configFactory()->get('rest.settings');
    $this->assertTrue($rest_settings->isNew());
  }

}
