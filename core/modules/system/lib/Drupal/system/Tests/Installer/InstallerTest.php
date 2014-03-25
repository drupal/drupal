<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Installer\InstallerTest.
 */

namespace Drupal\system\Tests\Installer;

use Drupal\simpletest\InstallerTestBase;

/**
 * Allows testing of the interactive installer.
 */
class InstallerTest extends InstallerTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Installer test',
      'description' => 'Tests the interactive installer.',
      'group' => 'Installer',
    );
  }

  /**
   * Ensures that the user page is available after installation.
   */
  public function testInstaller() {
    $this->assertUrl('user/1');
    $this->assertResponse(200);
    // Confirm that we are logged-in after installation.
    $this->assertText($this->root_user->getUsername());

    // Verify that the confirmation message appears.
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    $this->assertRaw(t('Congratulations, you installed @drupal!', array(
      '@drupal' => drupal_install_profile_distribution_name(),
    )));
  }

}
