<?php

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that installing from existing configuration works.
 *
 * @group Installer
 */
class InstallerExistingConfigSyncDriectoryProfileMismatchTest extends InstallerExistingConfigTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected $existingSyncDirectory = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getConfigTarball() {
    return __DIR__ . '/../../../fixtures/config_install/multilingual.tar.gz';
  }

  /**
   * Installer step: Configure settings.
   */
  protected function setUpSettings() {
    // Cause a profile mismatch by hacking the URL.
    $this->drupalGet(str_replace($this->profile, 'minimal', $this->getUrl()));
    parent::setUpSettings();
  }

  protected function setUpSite() {
    // This step will not occur because there is an error.
  }

  /**
   * Tests that profile mismatch fails to install.
   */
  public function testConfigSync() {
    $this->htmlOutput(NULL);
    $this->assertTitle('Configuration validation | Drupal');
    $this->assertText('The configuration synchronization failed validation.');
    $this->assertText('The selected installation profile minimal does not match the profile stored in configuration testing_config_install_multilingual.');

    // Ensure there is no continuation button.
    $this->assertNoText('Save and continue');
    $this->assertNoFieldById('edit-submit');
  }

}
