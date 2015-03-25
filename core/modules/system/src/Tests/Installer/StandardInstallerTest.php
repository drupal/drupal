<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\StandardInstallerTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Tests the interactive installer installing the standard profile.
 *
 * @group Installer
 */
class StandardInstallerTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    // Verify that the confirmation message appears.
    require_once \Drupal::root() . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', array(
      '@drupal' => drupal_install_profile_distribution_name(),
    )));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpSite() {
    // Test that the correct theme is being used.
    $this->assertNoRaw('bartik');
    $this->assertRaw('themes/seven/css/theme/install-page.css');
    parent::setUpSite();
  }


}
