<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Installer;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that we handle module dependency resolution during install.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerDependenciesResolutionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Verifies that the exception message in the profile step is correct.
   */
  public function testDependenciesResolution(): void {
    // Requires install.inc to be able to use drupal_verify_profile.
    require_once dirname(__FILE__, 7) . '/includes/install.inc';

    $info = drupal_verify_profile([
      'parameters' => ['profile' => 'testing_missing_dependencies'],
      'profile_info' => install_profile_info('testing_missing_dependencies'),
    ]);

    $message = $info['required_modules']['description']->render();
    $this->assertStringContainsString('Fictional', $message);
    $this->assertStringContainsString('Missing_module1', $message);
    $this->assertStringContainsString('Missing_module2', $message);
    $this->assertStringNotContainsString('Block', $message);
    $this->assertStringNotContainsString('Node', $message);
  }

}
