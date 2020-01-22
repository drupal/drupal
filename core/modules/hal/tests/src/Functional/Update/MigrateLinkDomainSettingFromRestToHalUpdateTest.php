<?php

namespace Drupal\Tests\hal\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * 'link_domain' is migrated from 'rest.settings' to 'hal.settings'.
 *
 * @see https://www.drupal.org/node/2758897
 *
 * @group hal
 * @group legacy
 */
class MigrateLinkDomainSettingFromRestToHalUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.hal-hal_update_8301.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.rest-hal_update_8301.php',
    ];
  }

  /**
   * Tests hal_update_8301().
   */
  public function testLinkDomainMigratedFromRestSettingsToHalSettings() {
    // Make sure we have the expected values before the update.
    $hal_settings = $this->config('hal.settings');
    $this->assertIdentical([], $hal_settings->getRawData());
    $rest_settings = $this->config('rest.settings');
    $this->assertTrue(array_key_exists('link_domain', $rest_settings->getRawData()));
    $this->assertIdentical('http://example.com', $rest_settings->getRawData()['link_domain']);

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $hal_settings = \Drupal::configFactory()->get('hal.settings');
    $this->assertTrue(array_key_exists('link_domain', $hal_settings->getRawData()));
    $this->assertIdentical('http://example.com', $hal_settings->getRawData()['link_domain']);
    $rest_settings = $this->config('rest.settings');
    $this->assertFalse(array_key_exists('link_domain', $rest_settings->getRawData()));
  }

}
