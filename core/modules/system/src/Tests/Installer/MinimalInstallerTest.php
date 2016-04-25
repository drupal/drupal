<?php

namespace Drupal\system\Tests\Installer;

use Drupal\KernelTests\AssertConfigTrait;

/**
 * Tests the interactive installer installing the minimal profile.
 *
 * @group Installer
 */
class MinimalInstallerTest extends ConfigAfterInstallerTestBase {

  use AssertConfigTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * Ensures that the exported minimal configuration is up to date.
   */
  public function testMinimalConfig() {
    $this->assertInstalledConfig([]);
  }

}
