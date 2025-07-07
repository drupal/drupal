<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that installing from existing configuration without a profile works.
 */
#[Group('Installer')]
class InstallerExistingConfigNoProfileTest extends InstallerExistingConfigTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = FALSE;

}
