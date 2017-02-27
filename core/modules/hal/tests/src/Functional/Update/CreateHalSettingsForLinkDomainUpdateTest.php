<?php

namespace Drupal\Tests\hal\Functional\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that 'hal.settings' is created, to store 'link_domain'.
 *
 * @see https://www.drupal.org/node/2758897
 *
 * @group hal
 */
class CreateHalSettingsForLinkDomainUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/drupal-8.hal-hal_update_8301.php',
    ];
  }

  /**
   * Tests hal_update_8301().
   */
  public function testHalSettingsCreated() {
    // Make sure we have the expected values before the update.
    $hal_settings = $this->config('hal.settings');
    $this->assertIdentical([], $hal_settings->getRawData());

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $hal_settings = \Drupal::configFactory()->get('hal.settings');
    $this->assertTrue(array_key_exists('link_domain', $hal_settings->getRawData()));
    $this->assertIdentical(NULL, $hal_settings->getRawData()['link_domain']);
  }

}
