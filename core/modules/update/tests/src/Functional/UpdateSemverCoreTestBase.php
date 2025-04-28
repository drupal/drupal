<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Base class for Update Status semantic versioning tests of Drupal core.
 *
 * This wires up the protected data from UpdateSemverTestBase for Drupal core
 * with semantic version releases.
 */
class UpdateSemverCoreTestBase extends UpdateSemverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $updateTableLocator = 'table.update';

  /**
   * {@inheritdoc}
   */
  protected $updateProject = 'drupal';

  /**
   * {@inheritdoc}
   */
  protected $projectTitle = 'Drupal';

  /**
   * {@inheritdoc}
   */
  protected function setProjectInstalledVersion($version) {
    $this->mockDefaultExtensionsInfo(['version' => $version]);
  }

}
