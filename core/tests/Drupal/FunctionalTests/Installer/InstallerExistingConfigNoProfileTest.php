<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

/**
 * Verifies that installing from existing configuration without a profile works.
 *
 * @group Installer
 */
class InstallerExistingConfigNoProfileTest extends InstallerExistingConfigTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = FALSE;

}
