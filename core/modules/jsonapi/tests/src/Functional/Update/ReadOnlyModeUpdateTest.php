<?php

namespace Drupal\Tests\jsonapi\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that existing sites have the new read-only mode to "off".
 *
 * @see jsonapi_update_8701()
 * @see https://www.drupal.org/project/drupal/issues/3039568
 *
 * @group jsonapi
 * @group Update
 * @group legacy
 */
class ReadOnlyModeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonapi'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.jsonapi-jsonapi_update_8701.php',
    ];
  }

  /**
   * Tests jsonapi_update_8701().
   */
  public function testBcReadOnlyModeSettingAdded() {
    // Make sure we have the expected values before the update.
    $jsonapi_settings = $this->config('jsonapi.settings');
    $this->assertFalse(array_key_exists('read_only', $jsonapi_settings->getRawData()));

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $jsonapi_settings = $this->config('jsonapi.settings');
    $this->assertTrue(array_key_exists('read_only', $jsonapi_settings->getRawData()));
    $this->assertFalse($jsonapi_settings->get('read_only'));
  }

}
