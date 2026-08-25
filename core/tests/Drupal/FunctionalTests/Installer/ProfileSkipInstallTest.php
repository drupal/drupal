<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests not installing a profile that opts out of it.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class ProfileSkipInstallTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_skip_install';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the profile was never installed.
   */
  public function testProfileNotInstalled(): void {
    // The profile skips install, so should never have been installed.
    $extensions = $this->config('core.extension')->get();
    $this->assertArrayNotHasKey($this->profile, $extensions['module']);
    $this->assertArrayNotHasKey('profile', $extensions);
  }

}
