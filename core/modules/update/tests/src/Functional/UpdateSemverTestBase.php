<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Common test methods for projects that use semver version releases.
 *
 * For classes that extend this class, the XML fixtures they will start with
 * ::$projectTitle.
 *
 * @group update
 */
abstract class UpdateSemverTestBase extends UpdateTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['update_test', 'update', 'language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The title of the project being tested.
   *
   * @var string
   */
  protected $projectTitle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'view update notifications',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('local_actions_block');
  }

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
          $this->assertUpdateTableElementContains(Link::fromTextAndUrl('9.0.0', Url::fromUri("http://example.com/{$this->updateProject}-9-0-0-release"))->toString());
          $this->assertUpdateTableElementContains(Link::fromTextAndUrl('Release notes', Url::fromUri("http://example.com/{$this->updateProject}-9-0-0-release"))->toString());
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
   * Tests the Update Manager module when a security update is available.
   *
   * @param string $site_patch_version
   *   The patch version to set the site to for testing.
   * @param string[] $expected_security_releases
   *   The security releases, if any, that the status report should recommend.
   * @param string $expected_update_message_type
   *   The type of update message expected.
   * @param string $fixture
   *   The test fixture that contains the test XML.
   *
   * @dataProvider securityUpdateAvailabilityProvider
   */
  public function testSecurityUpdateAvailability($site_patch_version, array $expected_security_releases, $expected_update_message_type, $fixture) {
    $this->setProjectInstalledVersion("8.$site_patch_version");
    $this->refreshUpdateStatus([$this->updateProject => $fixture]);
    $this->assertSecurityUpdates("{$this->updateProject}-8", $expected_security_releases, $expected_update_message_type, $this->updateTableLocator);
  }

  /**
   * Data provider method for testSecurityUpdateAvailability().
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - [::$updateProject].sec.0.1_0.2.xml
   *   - 8.0.2 Security update
   *   - 8.0.1 Security update, Insecure
   *   - 8.0.0 Insecure
   * - [::$updateProject].sec.0.2.xml
   *   - 8.0.2 Security update
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   * - [::$updateProject].sec.2.0-rc2.xml
   *   - 8.2.0-rc2 Security update
   *   - 8.2.0-rc1 Insecure
   *   - 8.2.0-beta2 Insecure
   *   - 8.2.0-beta1 Insecure
   *   - 8.2.0-alpha2 Insecure
   *   - 8.2.0-alpha1 Insecure
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2 Security update
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   * - [::$updateProject].sec.1.2.xml
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2
   *   - 8.0.1
   *   - 8.0.0
   * - [::$updateProject].sec.1.2_insecure.xml
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2 Insecure
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   * - [::$updateProject].sec.1.2_insecure-unsupported
   *   This file has the exact releases as
   *   [::$updateProject].sec.1.2_insecure.xml. It has a different value for
   *   'supported_branches' that does not contain '8.0.'. It is used to ensure
   *   that the "Security update required!" is displayed even if the currently
   *   installed version is in an unsupported branch.
   * - [::$updateProject].sec.2.0-rc2-b.xml
   *   - 8.2.0-rc2
   *   - 8.2.0-rc1
   *   - 8.2.0-beta2
   *   - 8.2.0-beta1
   *   - 8.2.0-alpha2
   *   - 8.2.0-alpha1
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2 Security update
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   */
  public function securityUpdateAvailabilityProvider() {
    $test_cases = [
      // Security release available for site minor release 0.
      // No releases for next minor.
      '0.0, 0.2' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['0.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.0.2',
      ],
      // Site on latest security release available for site minor release 0.
      // Minor release 1 also has a security release, and the current release
      // is marked as insecure.
      '0.2, 0.2' => [
        'site_patch_version' => '0.2',
        'expected_security_release' => ['1.2', '2.0-rc2'],
        'expected_update_message_type' => static::UPDATE_AVAILABLE,
        'fixture' => 'sec.2.0-rc2',
      ],
      // Two security releases available for site minor release 0.
      // 0.1 security release marked as insecure.
      // No releases for next minor.
      '0.0, 0.1 0.2' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['0.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.0.1_0.2',
      ],
      // Security release available for site minor release 1.
      // No releases for next minor.
      '1.0, 1.2' => [
        'site_patch_version' => '1.0',
        'expected_security_releases' => ['1.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.1.2',
      ],
      // Security release available for site minor release 0.
      // Security release also available for next minor.
      '0.0, 0.2 1.2' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['0.2', '1.2', '2.0-rc2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.2.0-rc2',
      ],
      // No newer security release for site minor 1.
      // Previous minor has security release.
      '1.2, 0.2 1.2' => [
        'site_patch_version' => '1.2',
        'expected_security_releases' => [],
        'expected_update_message_type' => static::UPDATE_NONE,
        'fixture' => 'sec.2.0-rc2',
      ],
      // No security release available for site minor release 0.
      // Security release available for next minor.
      '0.0, 1.2, insecure' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['1.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.1.2_insecure',
      ],
      // No security release available for site minor release 0.
      // Site minor is not a supported branch.
      // Security release available for next minor.
      '0.0, 1.2, insecure-unsupported' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['1.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.1.2_insecure-unsupported',
      ],
      // All releases for minor 0 are secure.
      // Security release available for next minor.
      '0.0, 1.2, secure' => [
        'site_patch_version' => '0.0',
        'expected_security_release' => ['1.2'],
        'expected_update_message_type' => static::UPDATE_AVAILABLE,
        'fixture' => 'sec.1.2',
      ],
      '0.2, 1.2, secure' => [
        'site_patch_version' => '0.2',
        'expected_security_release' => ['1.2'],
        'expected_update_message_type' => static::UPDATE_AVAILABLE,
        'fixture' => 'sec.1.2',
      ],
      // Site on 2.0-rc2 which is a security release.
      '2.0-rc2, 0.2 1.2' => [
        'site_patch_version' => '2.0-rc2',
        'expected_security_releases' => [],
        'expected_update_message_type' => static::UPDATE_NONE,
        'fixture' => 'sec.2.0-rc2',
      ],
      // Ensure that 8.0.2 security release is not shown because it is earlier
      // version than 1.0.
      '1.0, 0.2 1.2' => [
        'site_patch_version' => '1.0',
        'expected_security_releases' => ['1.2', '2.0-rc2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.2.0-rc2',
      ],
    ];
    $pre_releases = [
      '2.0-alpha1',
      '2.0-alpha2',
      '2.0-beta1',
      '2.0-beta2',
      '2.0-rc1',
      '2.0-rc2',
    ];

    foreach ($pre_releases as $pre_release) {
      // If the site is on an alpha/beta/RC of an upcoming minor and none of the
      // alpha/beta/RC versions are marked insecure, no security update should
      // be required.
      $test_cases["Pre-release:$pre_release, no security update"] = [
        'site_patch_version' => $pre_release,
        'expected_security_releases' => [],
        'expected_update_message_type' => $pre_release === '2.0-rc2' ? static::UPDATE_NONE : static::UPDATE_AVAILABLE,
        'fixture' => 'sec.2.0-rc2-b',
      ];
      // If the site is on an alpha/beta/RC of an upcoming minor and there is
      // an RC version with a security update, it should be recommended.
      $test_cases["Pre-release:$pre_release, security update"] = [
        'site_patch_version' => $pre_release,
        'expected_security_releases' => $pre_release === '2.0-rc2' ? [] : ['2.0-rc2'],
        'expected_update_message_type' => $pre_release === '2.0-rc2' ? static::UPDATE_NONE : static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.2.0-rc2',
      ];
    }
    return $test_cases;
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

  /**
   * {@inheritdoc}
   */
  protected function refreshUpdateStatus($xml_map, $url = 'update-test') {
    if (!isset($xml_map['drupal'])) {
      $xml_map['drupal'] = '0.0';
    }
    parent::refreshUpdateStatus($xml_map, $url);
  }

  /**
   * Sets the project installed version.
   *
   * @param string $version
   *   The version number.
   */
  abstract protected function setProjectInstalledVersion($version);

}
