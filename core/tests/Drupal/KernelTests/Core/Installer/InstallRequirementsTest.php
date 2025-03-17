<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Installer;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for install requirements.
 *
 * @group Installer
 */
class InstallRequirementsTest extends KernelTestBase {

  /**
   * Confirm installer checks requirements in designated classes.
   */
  public function testRequirements(): void {
    require_once 'core/includes/install.inc';

    $this->assertFalse(isset($GLOBALS['module_install_requirements']));
    drupal_check_module('module_install_requirements');
    $this->assertTrue(isset($GLOBALS['module_install_requirements']));
  }

  /**
   * Tests that the installer returns false if module requirements are not met.
   */
  public function testRequirementsFailure(): void {
    require_once 'core/includes/install.inc';
    $this->assertFalse(drupal_check_module('module_install_unmet_requirements'));
  }

}
