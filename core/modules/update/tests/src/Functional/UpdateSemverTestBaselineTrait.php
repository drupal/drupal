<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides test methods for semver tests shared between core and contrib.
 *
 * All of this "baseline" semver behavior should be the same for both Drupal
 * core and contributed projects that use semantic versioning.
 */
trait UpdateSemverTestBaselineTrait {

  /**
   * Tests the Update Manager module when no updates are available.
   *
   * The XML fixture file 'drupal.1.0.xml' which is one of the XML files this
   * test uses also contains 2 extra releases that are newer than '8.0.1'. These
   * releases will not show as available updates because of the following
   * reasons:
   * - '8.0.2' is an unpublished release.
   * - '8.0.3' is marked as 'Release type' 'Unsupported'.
   */
  public function testNoUpdatesAvailable() {
    foreach ([0, 1] as $minor_version) {
      foreach ([0, 1] as $patch_version) {
        foreach (['-alpha1', '-beta1', ''] as $extra_version) {
          $this->setProjectInstalledVersion("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus([$this->updateProject => "$minor_version.$patch_version" . $extra_version]);
          $this->standardTests();
          // The XML test fixtures for this method all contain the '8.2.0'
          // release but because '8.2.0' is not in a supported branch it will
          // not be in the available updates.
          $this->assertUpdateTableElementNotContains('8.2.0');
          $this->assertUpdateTableTextContains('Up to date');
          $this->assertUpdateTableTextNotContains('Update available');
          $this->assertUpdateTableTextNotContains('Security update required!');
          $this->assertUpdateTableElementContains('check.svg');
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when one normal update is available.
   */
  public function testNormalUpdateAvailable() {
    $this->setProjectInstalledVersion('8.0.0');

    // Ensure that the update check requires a token.
    $this->drupalGet('admin/reports/updates/check');
    $this->assertSession()->statusCodeEquals(403);

    foreach ([0, 1] as $minor_version) {
      foreach (['-alpha1', '-beta1', ''] as $extra_version) {
        $full_version = "8.$minor_version.1$extra_version";
        $this->refreshUpdateStatus([$this->updateProject => "$minor_version.1" . $extra_version]);
        $this->standardTests();
        $this->assertUpdateTableTextNotContains('Security update required!');
        // The XML test fixtures for this method all contain the '8.2.0' release
        // but because '8.2.0' is not in a supported branch it will not be in
        // the available updates.
        $this->assertSession()->responseNotContains('8.2.0');
        switch ($minor_version) {
          case 0:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertUpdateTableTextNotContains('Up to date');
              $this->assertUpdateTableTextContains('Update available');
              $this->assertVersionUpdateLinks('Recommended version:', $full_version);
              $this->assertUpdateTableTextNotContains('Latest version:');
              $this->assertUpdateTableElementContains('warning.svg');
            }
            // Only unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertUpdateTableTextContains('Up to date');
              $this->assertUpdateTableTextNotContains('Update available');
              $this->assertUpdateTableTextNotContains('Recommended version:');
              $this->assertVersionUpdateLinks('Latest version:', $full_version);
              $this->assertUpdateTableElementContains('check.svg');
            }
            break;

          case 1:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertUpdateTableTextNotContains('Up to date');
              $this->assertUpdateTableTextContains('Update available');
              $this->assertVersionUpdateLinks('Recommended version:', $full_version);
              $this->assertUpdateTableTextNotContains('Latest version:');
              $this->assertUpdateTableElementContains('warning.svg');
            }
            // Both stable and unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertUpdateTableTextNotContains('Up to date');
              $this->assertUpdateTableTextContains('Update available');
              $this->assertVersionUpdateLinks('Recommended version:', '8.1.0');
              $this->assertVersionUpdateLinks('Latest version:', $full_version);
              $this->assertUpdateTableElementContains('warning.svg');
            }
            break;
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when a major update is available.
   */
  public function testMajorUpdateAvailable() {
    foreach ([0, 1] as $minor_version) {
      foreach ([0, 1] as $patch_version) {
        foreach (['-alpha1', '-beta1', ''] as $extra_version) {
          $this->setProjectInstalledVersion("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus([$this->updateProject => '9']);
          $this->standardTests();
          $this->assertUpdateTableTextNotContains('Security update required!');
          $this->assertUpdateTableElementContains((string) Link::fromTextAndUrl('9.0.0', Url::fromUri("http://example.com/{$this->updateProject}-9-0-0-release"))->toString());
          $this->assertUpdateTableElementContains((string) Link::fromTextAndUrl('Release notes', Url::fromUri("http://example.com/{$this->updateProject}-9-0-0-release"))->toString());
          $this->assertUpdateTableTextNotContains('Up to date');
          $this->assertUpdateTableTextContains('Not supported!');
          $this->assertUpdateTableTextContains('Recommended version:');
          $this->assertUpdateTableTextNotContains('Latest version:');
          $this->assertUpdateTableElementContains('error.svg');
        }
      }
    }
  }

  /**
   * Tests messages when a project release is unpublished.
   *
   * This test confirms that revoked messages are displayed regardless of
   * whether the installed version is in a supported branch or not. This test
   * relies on 2 test XML fixtures that are identical except for the
   * 'supported_branches' value:
   * - [::$updateProject].1.0.xml
   *    'supported_branches' is '8.0.,8.1.'.
   * - [::$updateProject].1.0-unsupported.xml
   *    'supported_branches' is '8.1.'.
   * They both have an '8.0.2' release that is unpublished and an '8.1.0'
   * release that is published and is the expected update.
   */
  public function testRevokedRelease() {
    foreach (['1.0', '1.0-unsupported'] as $fixture) {
      $this->setProjectInstalledVersion('8.0.2');
      $this->refreshUpdateStatus([$this->updateProject => $fixture]);
      $this->standardTests();
      $this->confirmRevokedStatus('8.0.2', '8.1.0', 'Recommended version:');
    }
  }

  /**
   * Tests messages when a project release is marked unsupported.
   *
   * This test confirms unsupported messages are displayed regardless of whether
   * the installed version is in a supported branch or not. This test relies on
   * 2 test XML fixtures that are identical except for the 'supported_branches'
   * value:
   * - [::$updateProject].1.0.xml
   *    'supported_branches' is '8.0.,8.1.'.
   * - [::$updateProject].1.0-unsupported.xml
   *    'supported_branches' is '8.1.'.
   * They both have an '8.0.3' release that has the 'Release type' value of
   * 'unsupported' and an '8.1.0' release that has the 'Release type' value of
   * 'supported' and is the expected update.
   */
  public function testUnsupportedRelease() {
    foreach (['1.0', '1.0-unsupported'] as $fixture) {
      $this->setProjectInstalledVersion('8.0.3');
      $this->refreshUpdateStatus([$this->updateProject => $fixture]);
      $this->standardTests();
      $this->confirmUnsupportedStatus('8.0.3', '8.1.0', 'Recommended version:');
    }
  }

}
