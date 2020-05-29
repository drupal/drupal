<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that profiles invalid config can not be installed.
 *
 * @group Installer
 */
class InstallerExistingConfigNoConfigTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected $profile = 'no_config_profile';

  /**
   * Final installer step: Configure site.
   */
  protected function setUpSite() {
    // There are errors therefore there is nothing to do here.
    return;
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install_no_config.tar.gz';
  }

  /**
   * Tests that profiles with an empty config/sync directory do not work.
   */
  public function testConfigSync() {
    $this->assertSession()->titleEquals('Configuration validation | Drupal');
    $this->assertText('The configuration synchronization failed validation.');
    $this->assertText('This import is empty and if applied would delete all of your configuration, so has been rejected.');

    // Ensure there is no continuation button.
    $this->assertNoText('Save and continue');
    $this->assertNoFieldById('edit-submit');
  }

}
