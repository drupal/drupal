<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Testing installing from config without system.site.
 *
 * @group Installer
 */
class InstallerExistingConfigNoSystemSiteTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // File API functions are not available yet.
    unlink($this->siteDirectory . '/profiles/' . $this->profile . '/config/sync/system.site.yml');
  }

  /**
   * {@inheritdoc}
   */
  public function setUpSite() {
    // There are are errors. Therefore, there is nothing to do here.
  }

  /**
   * Tests that profiles with no system.site do not work.
   */
  public function testConfigSync() {
    $this->htmlOutput(NULL);
    $this->assertTitle('Configuration validation | Drupal');
    $this->assertText('The configuration synchronization failed validation.');
    $this->assertText('This import does not contain system.site configuration, so has been rejected.');

    // Ensure there is no continuation button.
    $this->assertNoText('Save and continue');
    $this->assertNoFieldById('edit-submit');
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install.tar.gz';
  }

}
