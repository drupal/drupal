<?php

namespace Drupal\Tests\update\Functional;

/**
 * Base class for Update manager semantic versioning tests of contrib projects.
 *
 * This wires up the protected data from UpdateSemverTestBase for a contrib
 * module with semantic version releases.
 */
class UpdateSemverContribTestBase extends UpdateSemverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $updateTableLocator = 'table.update:nth-of-type(2)';

  /**
   * {@inheritdoc}
   */
  protected $updateProject = 'semver_test';

  /**
   * {@inheritdoc}
   */
  protected $projectTitle = 'Semver Test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['semver_test'];

  /**
   * {@inheritdoc}
   */
  protected function setProjectInstalledVersion($version) {
    $this->mockInstalledExtensionsInfo([
      $this->updateProject => [
        'project' => $this->updateProject,
        'version' => $version,
        'hidden' => FALSE,
      ],
      // Ensure Drupal core on the same version for all test runs.
      'drupal' => [
        'project' => 'drupal',
        'version' => '8.0.0',
        'hidden' => FALSE,
      ],
    ]);
    $this->mockDefaultExtensionsInfo(['version' => '8.0.0']);
  }

}
