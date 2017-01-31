<?php

namespace Drupal\Tests\serialization\Functional\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that 'serialization.settings' is created, to store 'link_domain'.
 *
 * @see https://www.drupal.org/node/2758897
 *
 * @group serialization
 */
class CreateSerializationSettingsForLinkDomainUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['serialization'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.serialization-serialization_update_8301.php',
    ];
  }

  /**
   * Tests serialization_update_8301().
   */
  public function testSerializationSettingsCreated() {
    // Make sure we have the expected values before the update.
    $serialization_settings = $this->config('serialization.settings');
    $this->assertIdentical([], $serialization_settings->getRawData());

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $serialization_settings = \Drupal::configFactory()->get('serialization.settings');
    $this->assertTrue(array_key_exists('link_domain', $serialization_settings->getRawData()));
    $this->assertIdentical(NULL, $serialization_settings->getRawData()['link_domain']);
  }

}
