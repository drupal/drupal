<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Base class for Update Status semantic versioning tests of contrib projects.
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

  /**
   * Tests updates from legacy versions to the semver versions.
   */
  public function testUpdatesLegacyToSemver(): void {
    // Test cases where the legacy branch is in the XML 'supported_branches' and
    // when it is not.
    foreach ([TRUE, FALSE] as $legacy_supported) {
      // Test 2 legacy majors.
      foreach ([7, 8] as $legacy_major) {
        $semver_major = $legacy_major + 1;
        $installed_versions = [
          "8.x-$legacy_major.0-alpha1",
          "8.x-$legacy_major.0-beta1",
          "8.x-$legacy_major.0",
          "8.x-$legacy_major.1-alpha1",
          "8.x-$legacy_major.1-beta1",
          "8.x-$legacy_major.1",
        ];
        foreach ($installed_versions as $installed_version) {
          $this->setProjectInstalledVersion($installed_version);
          if ($legacy_supported) {
            $fixture = $legacy_major === 7 ? '8.1.0' : '9.1.0';
          }
          else {
            if ($legacy_major === 8) {
              continue;
            }
            $fixture = '8.1.0-legacy-unsupported';
          }

          $this->refreshUpdateStatus([$this->updateProject => $fixture]);
          $this->assertUpdateTableTextNotContains('Security update required!');
          $this->assertSession()->elementTextContains('css', $this->updateTableLocator . " .project-update__title", $installed_version);
          if ($legacy_supported) {
            // All installed versions should indicate that there is an update
            // available for the next major version of the module.
            // '$legacy_major.1.0' is shown for the next major version because
            // it is the latest full release for that major.
            // @todo Determine if both 8.0.0 and 8.0.1 should be expected as
            // "Also available" releases in
            // https://www.drupal.org/project/node/3100115.
            $this->assertVersionUpdateLinks('Also available:', "$semver_major.1.0");
            if ($installed_version === "8.x-$legacy_major.1") {
              $this->assertUpdateTableTextContains('Up to date');
              $this->assertUpdateTableTextNotContains('Update available');
            }
            else {
              $this->assertUpdateTableTextNotContains('Up to date');
              $this->assertUpdateTableTextContains('Update available');
              // All installed versions besides 8.x-$legacy_major.1 should
              // recommend 8.x-$legacy_major.1 because it is the latest full
              // release for the major.
              $this->assertVersionUpdateLinks('Recommended version:', "8.x-$legacy_major.1");
            }
          }
          else {
            // If '8.x-7.' is not in the XML 'supported_branches' value then the
            // latest release for the next major should always be recommended.
            $this->assertVersionUpdateLinks('Recommended version:', "$semver_major.1.0");
          }
        }
      }
    }
  }

}
