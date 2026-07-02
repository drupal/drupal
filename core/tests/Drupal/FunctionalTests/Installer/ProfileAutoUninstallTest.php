<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests automatically uninstalling a profile that opts into it.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class ProfileAutoUninstallTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_auto_uninstall';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the profile was automatically uninstalled.
   */
  public function testAutomaticUninstall(): void {
    // The profile included no in-use dependencies, and therefore should
    // have been successfully uninstalled.
    $extensions = $this->config('core.extension')->get();
    $this->assertArrayNotHasKey($this->profile, $extensions['module']);
    $this->assertArrayNotHasKey('profile', $extensions);
  }

}
