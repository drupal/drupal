<?php

namespace Drupal\Tests\update\Functional;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the Update Manager module through a series of functional tests using
 * mock XML data.
 *
 * @group update
 */
class UpdateCoreTest extends UpdateTestBase {

  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['update_test', 'update', 'language', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $updateTableLocator = 'table.update';

  /**
   * {@inheritdoc}
   */
  protected $updateProject = 'drupal';

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'administer modules', 'administer themes']);
    $this->drupalLogin($admin_user);
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Sets the version to x.x.x when no project-specific mapping is defined.
   *
   * @param string $version
   *   The version.
   */
  protected function setSystemInfo($version) {
    $setting = [
      '#all' => [
        'version' => $version,
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $setting)->save();
  }

  /**
   * Tests the Update Manager module when no updates are available.
   */
  public function testNoUpdatesAvailable() {
    foreach ([0, 1] as $minor_version) {
      foreach ([0, 1] as $patch_version) {
        foreach (['-alpha1', '-beta1', ''] as $extra_version) {
          $this->setSystemInfo("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus(['drupal' => "$minor_version.$patch_version" . $extra_version]);
          $this->standardTests();
          $this->assertText(t('Up to date'));
          $this->assertNoText(t('Update available'));
          $this->assertNoText(t('Security update required!'));
          $this->assertRaw('check.svg', 'Check icon was found.');
        }
      }
    }
  }

  /**
   * Tests the Update Manager module when one normal update is available.
   */
  public function testNormalUpdateAvailable() {
    $this->setSystemInfo('8.0.0');

    // Ensure that the update check requires a token.
    $this->drupalGet('admin/reports/updates/check');
    $this->assertResponse(403, 'Accessing admin/reports/updates/check without a CSRF token results in access denied.');

    foreach ([0, 1] as $minor_version) {
      foreach (['-alpha1', '-beta1', ''] as $extra_version) {
        $full_version = "8.$minor_version.1$extra_version";
        $this->refreshUpdateStatus(['drupal' => "$minor_version.1" . $extra_version]);
        $this->standardTests();
        $this->drupalGet('admin/reports/updates');
        $this->clickLink(t('Check manually'));
        $this->checkForMetaRefresh();
        $this->assertNoText(t('Security update required!'));
        switch ($minor_version) {
          case 0:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertVersionUpdateLinks('Recommended version:', $full_version);
              $this->assertNoText(t('Latest version:'));
              $this->assertRaw('warning.svg', 'Warning icon was found.');
            }
            // Only unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertText(t('Up to date'));
              $this->assertNoText(t('Update available'));
              $this->assertNoText(t('Recommended version:'));
              $this->assertVersionUpdateLinks('Latest version:', $full_version);
              $this->assertRaw('check.svg', 'Check icon was found.');
            }
            break;
          case 1:
            // Both stable and unstable releases are available.
            // A stable release is the latest.
            if ($extra_version == '') {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertVersionUpdateLinks('Recommended version:', $full_version);
              $this->assertNoText(t('Latest version:'));
              $this->assertRaw('warning.svg', 'Warning icon was found.');
            }
            // Both stable and unstable releases are available.
            // An unstable release is the latest.
            else {
              $this->assertNoText(t('Up to date'));
              $this->assertText(t('Update available'));
              $this->assertVersionUpdateLinks('Recommended version:', '8.1.0');
              $this->assertVersionUpdateLinks('Latest version:', $full_version);
              $this->assertRaw('warning.svg', 'Warning icon was found.');
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
          $this->setSystemInfo("8.$minor_version.$patch_version" . $extra_version);
          $this->refreshUpdateStatus(['drupal' => '9']);
          $this->standardTests();
          $this->drupalGet('admin/reports/updates');
          $this->clickLink(t('Check manually'));
          $this->checkForMetaRefresh();
          $this->assertNoText(t('Security update required!'));
          $this->assertRaw(Link::fromTextAndUrl('9.0.0', Url::fromUri("http://example.com/drupal-9-0-0-release"))->toString(), 'Link to release appears.');
          $this->assertRaw(Link::fromTextAndUrl(t('Download'), Url::fromUri("http://example.com/drupal-9-0-0.tar.gz"))->toString(), 'Link to download appears.');
          $this->assertRaw(Link::fromTextAndUrl(t('Release notes'), Url::fromUri("http://example.com/drupal-9-0-0-release"))->toString(), 'Link to release notes appears.');
          $this->assertNoText(t('Up to date'));
          $this->assertText(t('Not supported!'));
          $this->assertText(t('Recommended version:'));
          $this->assertNoText(t('Latest version:'));
          $this->assertRaw('error.svg', 'Error icon was found.');
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
    $this->setSystemInfo("8.$site_patch_version");
    $this->refreshUpdateStatus(['drupal' => $fixture]);
    $this->assertSecurityUpdates('drupal-8', $expected_security_releases, $expected_update_message_type, 'table.update');
  }

  /**
   * Data provider method for testSecurityUpdateAvailability().
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - drupal.sec.0.1_0.2.xml
   *   - 8.0.2 Security update
   *   - 8.0.1 Security update, Insecure
   *   - 8.0.0 Insecure
   * - drupal.sec.0.2.xml
   *   - 8.0.2 Security update
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   * - drupal.sec.0.2-rc2.xml
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
   * - drupal.sec.1.2.xml
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2
   *   - 8.0.1
   *   - 8.0.0
   * - drupal.sec.1.2_insecure.xml
   *   - 8.1.2 Security update
   *   - 8.1.1 Insecure
   *   - 8.1.0 Insecure
   *   - 8.0.2 Insecure
   *   - 8.0.1 Insecure
   *   - 8.0.0 Insecure
   * - drupal.sec.0.2-rc2-b.xml
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
        'fixture' => 'sec.0.2-rc2',
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
        'fixture' => 'sec.0.2-rc2',
      ],
      // No newer security release for site minor 1.
      // Previous minor has security release.
      '1.2, 0.2 1.2' => [
        'site_patch_version' => '1.2',
        'expected_security_releases' => [],
        'expected_update_message_type' => static::UPDATE_NONE,
        'fixture' => 'sec.0.2-rc2',
      ],
      // No security release available for site minor release 0.
      // Security release available for next minor.
      '0.0, 1.2, insecure' => [
        'site_patch_version' => '0.0',
        'expected_security_releases' => ['1.2'],
        'expected_update_message_type' => static::SECURITY_UPDATE_REQUIRED,
        'fixture' => 'sec.1.2_insecure',
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
        'fixture' => 'sec.0.2-rc2',
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

    // If the site is on an alpha/beta/RC of an upcoming minor and none of the
    // alpha/beta/RC versions are marked insecure, no security update should be
    // required.
    foreach ($pre_releases as $pre_release) {
      $test_cases["Pre-release:$pre_release, no security update"] = [
        'site_patch_version' => $pre_release,
        'expected_security_releases' => [],
        'expected_update_message_type' => $pre_release === '2.0-rc2' ? static::UPDATE_NONE : static::UPDATE_AVAILABLE,
        'fixture' => 'sec.0.2-rc2-b',
      ];
    }

    // @todo In https://www.drupal.org/node/2865920 add test cases:
    //   - For all pre-releases for 8.2.0 except 8.2.0-rc2 using the
    //     'sec.0.2-rc2' fixture to ensure that 8.2.0-rc2 is the only security
    //     update.
    //   - For 8.1.0 using fixture 'sec.0.2-rc2' to ensure that only security
    //     updates are 8.1.2 and 8.2.0-rc2.
    return $test_cases;
  }

  /**
   * Tests the security coverage messages for Drupal core versions.
   *
   * @param string $installed_version
   *   The installed Drupal version to test.
   * @param string $fixture
   *   The test fixture that contains the test XML.
   * @param string $requirements_section_heading
   *   The requirements section heading.
   * @param string $message
   *   The expected coverage message.
   * @param string $mock_date
   *   The mock date to use if needed in the format CCYY-MM-DD. If an empty
   *   string is provided, no mock date will be used.
   *
   * @dataProvider securityCoverageMessageProvider
   */
  public function testSecurityCoverageMessage($installed_version, $fixture, $requirements_section_heading, $message, $mock_date) {
    \Drupal::state()->set('update_test.mock_date', $mock_date);
    $this->setSystemInfo($installed_version);
    $this->refreshUpdateStatus(['drupal' => $fixture]);
    $this->drupalGet('admin/reports/status');

    if (empty($requirements_section_heading)) {
      $this->assertSession()->pageTextNotContains('Drupal core security coverage');
      return;
    }

    $all_requirements_details = $this->getSession()->getPage()->findAll(
      'css',
      'details.system-status-report__entry:contains("Drupal core security coverage")'
    );
    // Ensure we only have 1 security message section.
    $this->assertCount(1, $all_requirements_details);
    $requirements_details = $all_requirements_details[0];
    // Ensure that messages are under the correct heading which could be
    // 'Checked', 'Warnings found', or 'Errors found'.
    $requirements_section_element = $requirements_details->getParent();
    $this->assertCount(1, $requirements_section_element->findAll('css', "h3:contains('$requirements_section_heading')"));
    $actual_message = $requirements_details->find('css', 'div.description')->getText();
    $this->assertNotEmpty($actual_message);
    $this->assertEquals($message, $actual_message);
  }

  /**
   * Dataprovider for testSecurityCoverageMessage().
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - drupal.sec.2.0_3.0-rc1.xml
   *   - 8.2.0
   *   - 8.3.0-rc1
   * - drupal.sec.2.0.xml
   *   - 8.2.0
   * - drupal.sec.2.0_9.0.0.xml
   *   - 8.2.0
   *   - 9.0.0
   * - drupal.sec.9.0.xml
   *   - 8.9.0
   * - drupal.sec.9.9.0.xml
   *   - 9.9.0
   */
  public function securityCoverageMessageProvider() {
    $release_coverage_message = 'Visit the release cycle overview for more information on supported releases.';
    $see_available_message = 'See the available updates page for more information.';
    $update_asap_message = 'Update to a supported minor as soon as possible to continue receiving security updates.';
    $update_soon_message = 'Update to a supported minor version soon to continue receiving security updates.';
    $test_cases = [
      '8.0.0, unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Errors found',
        'message' => "The installed minor version of Drupal (8.0), is no longer supported and will not receive security updates.$update_asap_message $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported with 3rc' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Warnings found',
        'message' => "The installed minor version of Drupal (8.1), will stop receiving official security support after the release of 8.3.0.Update to 8.2 or higher soon to continue receiving security updates. $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.2.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "The installed minor version of Drupal (8.1), will stop receiving official security support after the release of 8.3.0.Update to 8.2 or higher soon to continue receiving security updates. $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported with 3rc' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => 'Checked',
        'message' => "The installed minor version of Drupal (8.2), will stop receiving official security support after the release of 8.4.0.$release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0',
        'requirements_section_heading' => 'Checked',
        'message' => "The installed minor version of Drupal (8.2), will stop receiving official security support after the release of 8.4.0.$release_coverage_message",
        'mock_date' => '',
      ],
      // Ensure we don't show messages for pre-release or dev versions.
      '8.2.0-beta2, no message' => [
        'installed_version' => '8.2.0-beta2',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      '8.1.0-dev, no message' => [
        'installed_version' => '8.1.0-dev',
        'fixture' => 'sec.2.0_3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have been released.
      '8.0.0, 9 unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.2.0_9.0.0',
        'requirements_section_heading' => 'Errors found',
        'message' => "The installed minor version of Drupal (8.0), is no longer supported and will not receive security updates.$update_asap_message $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have not been released.
      '8.2.0, 9 warning' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.2.0_9.0.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "The installed minor version of Drupal (8.2), will stop receiving official security support after the release of 8.4.0.Update to 8.3 or higher soon to continue receiving security updates. $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
    ];

    // Drupal 8.8.x test cases.
    $test_cases += [
      // Ensure that a message is displayed during 8.8's active support.
      '8.8.0, supported' => [
        'installed_version' => '8.8.0',
        'fixture' => 'sec.9.0',
        'requirements_section_heading' => 'Checked',
        'message' => "The installed minor version of Drupal (8.8), will stop receiving official security support after 2020-12-02.$release_coverage_message",
        'mock_date' => '2020-06-01',
      ],
      // Ensure a warning is displayed if less than six months remain until the
      // end of 8.8's security coverage.
      '8.8.0, supported, 6 months warn' => [
        'installed_version' => '8.8.0',
        'fixture' => 'sec.9.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "The installed minor version of Drupal (8.8), will stop receiving official security support after 2020-12-02.$update_soon_message$release_coverage_message",
        'mock_date' => '2020-06-02',
      ],
    ];
    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['8.8.0, supported, last day warn'] = $test_cases['8.8.0, supported, 6 months warn'];
    $test_cases['8.8.0, supported, last day warn']['mock_date'] = '2020-12-01';

    // Ensure that if the 8.8 support window is finished a message is
    // displayed.
    $test_cases['8.8.0, support over'] = [
      'installed_version' => '8.8.0',
      'fixture' => 'sec.9.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "The installed minor version of Drupal (8.8), is no longer supported and will not receive security updates.$update_asap_message $see_available_message$release_coverage_message",
      'mock_date' => '2020-12-02',
    ];

    // Drupal 8.9 LTS test cases.
    $test_cases['8.9.0, lts supported'] = [
      'installed_version' => '8.9.0',
      'fixture' => 'sec.9.0',
      'requirements_section_heading' => 'Checked',
      'message' => "The installed minor version of Drupal (8.9), will stop receiving official security support after November 2021.$release_coverage_message",
      'mock_date' => '2021-01-01',
    ];
    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['8.9.0, lts supported, last day'] = $test_cases['8.9.0, lts supported'];
    $test_cases['8.9.0, lts supported, last day']['mock_date'] = '2021-10-31';

    // Ensure that if LTS support window is finished a message is displayed.
    $test_cases['8.9.0, lts support over'] = [
      'installed_version' => '8.9.0',
      'fixture' => 'sec.9.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "The installed minor version of Drupal (8.9), is no longer supported and will not receive security updates.$update_asap_message $see_available_message$release_coverage_message",
      'mock_date' => '2021-11-01',
    ];

    // Drupal 9 test cases.
    $test_cases += [
      // Ensure the end dates for 8.8 and 8.9 only apply to major version 8.
      '9.9.0' => [
        'installed_version' => '9.9.0',
        'fixture' => 'sec.9.9.0',
        'requirements_section_heading' => 'Checked',
        'message' => "The installed minor version of Drupal (9.9), will stop receiving official security support after the release of 9.11.0.$release_coverage_message",
        'mock_date' => '',
      ],
      '9.8.0' => [
        'installed_version' => '9.8.0',
        'fixture' => 'sec.9.9.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "The installed minor version of Drupal (9.8), will stop receiving official security support after the release of 9.10.0.Update to 9.9 or higher soon to continue receiving security updates. $see_available_message$release_coverage_message",
        'mock_date' => '',
      ],
    ];
    return $test_cases;

  }

  /**
   * Ensures proper results where there are date mismatches among modules.
   */
  public function testDatestampMismatch() {
    $system_info = [
      '#all' => [
        // We need to think we're running a -dev snapshot to see dates.
        'version' => '8.1.0-dev',
        'datestamp' => time(),
      ],
      'block' => [
        // This is 2001-09-09 01:46:40 GMT, so test for "2001-Sep-".
        'datestamp' => '1000000000',
      ],
    ];
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
    $this->refreshUpdateStatus(['drupal' => 'dev']);
    $this->assertNoText(t('2001-Sep-'));
    $this->assertText(t('Up to date'));
    $this->assertNoText(t('Update available'));
    $this->assertNoText(t('Security update required!'));
  }

  /**
   * Checks that running cron updates the list of available updates.
   */
  public function testModulePageRunCron() {
    $this->setSystemInfo('8.0.0');
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
      ->save();

    $this->cronRun();
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('No update information available.'));
  }

  /**
   * Checks that clearing the disk cache works.
   */
  public function testClearDiskCache() {
    $directories = [
      _update_manager_cache_directory(FALSE),
      _update_manager_extract_directory(FALSE),
    ];
    // Check that update directories does not exists.
    foreach ($directories as $directory) {
      $this->assertDirectoryNotExists($directory);
    }

    // Method must not fail if update directories do not exists.
    update_clear_update_disk_cache();
  }

  /**
   * Checks the messages at admin/modules when the site is up to date.
   */
  public function testModulePageUpToDate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.0'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when an update is missing.
   */
  public function testModulePageRegularUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertText(t('There are updates available for your version of Drupal.'));
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Checks the messages at admin/modules when a security update is missing.
   */
  public function testModulePageSecurityUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => 'sec.0.2'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Check manually'));
    $this->checkForMetaRefresh();
    $this->assertText(t('Checked available update data for one project.'));
    $this->drupalGet('admin/modules');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertText(t('There is a security update available for your version of Drupal.'));

    // Make sure admin/appearance warns you you're missing a security update.
    $this->drupalGet('admin/appearance');
    $this->assertNoText(t('There are updates available for your version of Drupal.'));
    $this->assertText(t('There is a security update available for your version of Drupal.'));

    // Make sure duplicate messages don't appear on Update status pages.
    $this->drupalGet('admin/reports/status');
    // We're expecting "There is a security update..." inside the status report
    // itself, but the message from
    // \Drupal\Core\Messenger\MessengerInterface::addStatus() appears as an li
    // so we can prefix with that and search for the raw HTML.
    $this->assertNoRaw('<li>' . t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));

    $this->drupalGet('admin/reports/updates/settings');
    $this->assertNoText(t('There is a security update available for your version of Drupal.'));
  }

  /**
   * Tests the Update Manager module when the update server returns 503 errors.
   */
  public function testServiceUnavailable() {
    $this->refreshUpdateStatus([], '503-error');
    // Ensure that no "Warning: SimpleXMLElement..." parse errors are found.
    $this->assertNoText('SimpleXMLElement');
    $this->assertUniqueText(t('Failed to get available update data for one project.'));
  }

  /**
   * Tests that exactly one fetch task per project is created and not more.
   */
  public function testFetchTasks() {
    $projecta = [
      'name' => 'aaa_update_test',
    ];
    $projectb = [
      'name' => 'bbb_update_test',
    ];
    $queue = \Drupal::queue('update_fetch_tasks');
    $this->assertEqual($queue->numberOfItems(), 0, 'Queue is empty');
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 1, 'Queue contains one item');
    update_create_fetch_task($projectb);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue contains two items');
    // Try to add project a again.
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue still contains two items');

    // Clear storage and try again.
    update_storage_clear();
    update_create_fetch_task($projecta);
    $this->assertEqual($queue->numberOfItems(), 2, 'Queue contains two items');
  }

  /**
   * Checks language module in core package at admin/reports/updates.
   */
  public function testLanguageModuleUpdate() {
    $this->setSystemInfo('8.0.0');
    // Instead of using refreshUpdateStatus(), set these manually.
    $this->config('update.settings')
      ->set('fetch.url', Url::fromRoute('update_test.update_test')->setAbsolute()->toString())
      ->save();
    $this->config('update_test.settings')
      ->set('xml_map', ['drupal' => '0.1'])
      ->save();

    $this->drupalGet('admin/reports/updates');
    $this->assertText(t('Language'));
  }

  /**
   * Ensures that the local actions appear.
   */
  public function testLocalActions() {
    $admin_user = $this->drupalCreateUser(['administer site configuration', 'administer modules', 'administer software updates', 'administer themes']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/modules');
    $this->clickLink(t('Install new module'));
    $this->assertUrl('admin/modules/install');

    $this->drupalGet('admin/appearance');
    $this->clickLink(t('Install new theme'));
    $this->assertUrl('admin/theme/install');

    $this->drupalGet('admin/reports/updates');
    $this->clickLink(t('Install new module or theme'));
    $this->assertUrl('admin/reports/updates/install');
  }

  /**
   * {@inheritdoc}
   */
  protected function assertVersionUpdateLinks($label, $version, $download_version = NULL) {
    // Test XML files for Drupal core use '-' in the version number for the
    // download link.
    $download_version = str_replace('.', '-', $version);
    parent::assertVersionUpdateLinks($label, $version, $download_version);
  }

}
