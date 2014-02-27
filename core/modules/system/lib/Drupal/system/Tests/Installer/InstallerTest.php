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
  }

}
