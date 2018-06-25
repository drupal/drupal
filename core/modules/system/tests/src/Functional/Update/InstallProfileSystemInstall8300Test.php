<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Site\Settings;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests system_update_8300().
 *
 * @group Update
 * @group legacy
 */
class InstallProfileSystemInstall8300Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that the system_update_8300() runs as expected.
   *
   * @expectedDeprecation To access the install profile in Drupal 8 use \Drupal::installProfile() or inject the install_profile container parameter into your service. See https://www.drupal.org/node/2538996
   */
  public function testUpdate() {
    // Ensure the BC layers work and settings.php and configuration is in the
    // expected state before updating.
    $this->assertEqual('standard', \Drupal::installProfile());
    $this->assertEqual('standard', Settings::get('install_profile'), 'The install profile has not been written to settings.php.');
    $this->assertFalse($this->config('core.extension')->get('profile'), 'The install profile is not present in core.extension configuration.');

    $this->runUpdates();
    // Confirm that Drupal recognizes this distribution as the current profile.
    $this->assertEqual('standard', \Drupal::installProfile());
    $this->assertEqual('standard', Settings::get('install_profile'), 'The install profile has not been written to settings.php.');
    $this->assertEqual('standard', $this->config('core.extension')->get('profile'), 'The install profile has been written to core.extension configuration.');
  }

}
