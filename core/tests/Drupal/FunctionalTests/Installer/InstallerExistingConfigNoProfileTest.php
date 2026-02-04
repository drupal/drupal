<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies that installing from existing configuration without a profile works.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerExistingConfigNoProfileTest extends InstallerExistingConfigTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUpRequirementsProblem(): void {
  }

}
