<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures that the exported minimal configuration is up to date.
   */
  public function testMinimalConfig(): void {
    $this->assertInstalledConfig([]);
  }

}
