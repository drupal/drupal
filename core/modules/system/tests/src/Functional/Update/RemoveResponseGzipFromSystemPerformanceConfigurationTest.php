<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that response.gzip is removed from system.performance configuration.
 *
 * @group Update
 */
class RemoveResponseGzipFromSystemPerformanceConfigurationTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that response.gzip is removed from system.performance
   * configuration.
   */
  public function testUpdate() {
    \Drupal::configFactory()->getEditable('system.performance')
      ->set('response.gzip', 1)
      ->save();

    $this->runUpdates();

    $system_performance = \Drupal::config('system.performance')->get();
    $this->assertFalse(isset($system_performance['response.gzip']), 'Configuration response.gzip has been removed from system.performance.');
  }

}
