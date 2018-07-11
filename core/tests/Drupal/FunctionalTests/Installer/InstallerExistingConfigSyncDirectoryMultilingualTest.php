<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigSyncDirectoryMultilingualTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected $existingSyncDirectory = TRUE;

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    // Ensure the site name 'Multilingual' appears as expected in the 'Use
    // existing configuration' radio description.
    $this->assertSession()->pageTextContains('Install Multilingual using existing configuration.');
    return parent::setUpProfile();
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/multilingual.tar.gz';
  }

}
