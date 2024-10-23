<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Testing installing from config without system.site.
 *
 * @group Installer
 */
class InstallerExistingConfigNoSystemSiteTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    // File API functions are not available yet.
    unlink($this->siteDirectory . '/profiles/' . $this->profile . '/config/sync/system.site.yml');
  }

  /**
   * {@inheritdoc}
   */
  public function setUpSite(): void {
    // There are errors. Therefore, there is nothing to do here.
  }

  /**
   * Tests that profiles with no system.site do not work.
   */
  public function testConfigSync(): void {
    $this->htmlOutput(NULL);
    $this->assertSession()->titleEquals('Configuration validation | Drupal');
    $this->assertSession()->pageTextContains('The configuration synchronization failed validation.');
    $this->assertSession()->pageTextContains('This import does not contain system.site configuration, so has been rejected.');

    // Ensure there is no continuation button.
    $this->assertSession()->pageTextNotContains('Save and continue');
    $this->assertSession()->buttonNotExists('edit-submit');
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/testing_config_install';
  }

}
