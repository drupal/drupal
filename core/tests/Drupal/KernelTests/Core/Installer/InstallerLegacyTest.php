<?php

namespace Drupal\KernelTests\Core\Installer;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for installer related legacy API.
 *
 * @group legacy
 */
class InstallerLegacyTest extends KernelTestBase {

  /**
   * Tests drupal_installation_attempted().
   *
   * @expectedDeprecation drupal_installation_attempted() is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Use \Drupal\Core\Installer\InstallerKernel::installationAttempted() instead. See https://www.drupal.org/node/3035275
   */
  public function testDrupalInstallationAttempted() {
    $this->assertFalse(drupal_installation_attempted());
  }

}
