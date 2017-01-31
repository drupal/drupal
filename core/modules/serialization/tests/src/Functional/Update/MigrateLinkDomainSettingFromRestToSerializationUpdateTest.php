<?php

namespace Drupal\Tests\serialization\Functional\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * 'link_domain' is migrated from 'rest.settings' to 'serialization.settings'.
 *
 * @see https://www.drupal.org/node/2758897
 *
 * @group serialization
 */
class MigrateLinkDomainSettingFromRestToSerializationUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'serialization'];

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.serialization-serialization_update_8301.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.rest-serialization_update_8301.php',
    ];
  }

  /**
   * Tests serialization_update_8301().
   */
  public function testLinkDomainMigratedFromRestSettingsToSerializationSettings() {
    // Make sure we have the expected values before the update.
    $serialization_settings = $this->config('serialization.settings');
    $this->assertIdentical([], $serialization_settings->getRawData());
    $rest_settings = $this->config('rest.settings');
    $this->assertTrue(array_key_exists('link_domain', $rest_settings->getRawData()));
    $this->assertIdentical('http://example.com', $rest_settings->getRawData()['link_domain']);

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $serialization_settings = \Drupal::configFactory()->get('serialization.settings');
    $this->assertTrue(array_key_exists('link_domain', $serialization_settings->getRawData()));
    $this->assertIdentical('http://example.com', $serialization_settings->getRawData()['link_domain']);
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse(array_key_exists('link_domain', $rest_settings->getRawData()));
  }

}
