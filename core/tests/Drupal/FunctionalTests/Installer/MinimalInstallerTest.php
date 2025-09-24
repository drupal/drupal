<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\KernelTests\AssertConfigTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the interactive installer installing the minimal profile.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
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
