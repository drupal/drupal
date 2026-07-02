<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that profile-provided dependencies thwart auto-uninstall.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class ProfileAutoUninstallFailureTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_auto_uninstall_has_dependencies';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpSite(): void {
    try {
      parent::setUpSite();
      $this->fail('Expected an exception due to an in-use dependency.');
    }
    catch (\Throwable $e) {
      $this->assertStringContainsString("The install profile 'Testing - Automatic uninstall failure' is providing the following module(s): testing_in_use_dependency", $e->getMessage());
    }
  }

  /**
   * Tests that the profile cannot be automatically uninstalled.
   */
  public function testAutomaticUninstallFailure(): void {
    // Everything we're testing here happens in setUpSite().
  }

}
