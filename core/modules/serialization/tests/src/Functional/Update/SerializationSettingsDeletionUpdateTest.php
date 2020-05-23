<?php

namespace Drupal\Tests\serialization\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Ensures that update hook is run properly for deleting obsolete serialization settings.
 *
 * @group serialization
 */
class SerializationSettingsDeletionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensures that update hook is run for "serialization" module.
   */
  public function testUpdate() {
    $serialization_settings = $this->config('serialization.settings');
    $this->assertFalse($serialization_settings->isNew());
    $this->assertEquals(FALSE, $serialization_settings->get('bc_primitives_as_strings'));
    $this->assertEquals(TRUE, $serialization_settings->get('bc_timestamp_normalizer_unix'));

    $this->runUpdates();

    $serialization_settings = \Drupal::configFactory()->get('serialization.settings');
    $this->assertTrue($serialization_settings->isNew());
  }

}
