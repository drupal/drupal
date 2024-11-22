<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Tests the security coverage messages for Drupal core versions.
 *
 * @group update
 */
class UpdateSemverCoreSecurityCoverageTest extends UpdateSemverCoreTestBase {

  /**
   * Tests the security coverage messages for Drupal core versions.
   */
  public function testSecurityCoverageMessage(): void {
    foreach (static::securityCoverageMessageProvider() as $case) {
      $this->doTestSecurityCoverageMessage($case['installed_version'], $case['fixture'], $case['requirements_section_heading'], $case['message'], $case['mock_date']);
    }
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
   */
  protected function doTestSecurityCoverageMessage($installed_version, $fixture, $requirements_section_heading, $message, $mock_date): void {
    \Drupal::state()->set('update_test.mock_date', $mock_date);
    $this->setProjectInstalledVersion($installed_version);
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
    $actual_message = $requirements_details->find('css', 'div.system-status-report__entry__value')->getText();
    $this->assertNotEmpty($actual_message);
    $this->assertEquals($message, $actual_message);
  }

  /**
   * Data provider for testSecurityCoverageMessage().
   *
   * These test cases rely on the following fixtures containing the following
   * releases:
   * - drupal.sec.8.2.0_3.0-rc1.xml
   *   - 8.2.0
   *   - 8.3.0-rc1
   * - drupal.sec.8.2.0.xml
   *   - 8.2.0
   * - drupal.sec.8.2.0_9.0.0.xml
   *   - 8.2.0
   *   - 9.0.0
   * - drupal.sec.10.6.0.xml
   *   - 10.5.0
   *   - 10.6.0
   */
  protected static function securityCoverageMessageProvider() {
    $release_coverage_message = 'Visit the release cycle overview for more information on supported releases.';
    $coverage_ended_message = 'Coverage has ended';
    $update_asap_message = 'Update to a supported minor as soon as possible to continue receiving security updates.';
    $update_soon_message = 'Update to a supported version soon to continue receiving security updates.';
    $test_cases = [
      '8.0.0, unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.8.2.0_8.3.0-rc1',
        'requirements_section_heading' => 'Errors found',
        'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported with 3rc' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.8.2.0_8.3.0-rc1',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.3.0 Update to 8.2 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
      '8.1.0, supported' => [
        'installed_version' => '8.1.0',
        'fixture' => 'sec.8.2.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.3.0 Update to 8.2 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported with 3rc' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.8.2.0_8.3.0-rc1',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 8.4.0 $release_coverage_message",
        'mock_date' => '',
      ],
      '8.2.0, supported' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.8.2.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 8.4.0 $release_coverage_message",
        'mock_date' => '',
      ],
      // Ensure we don't show messages for pre-release or dev versions.
      '8.2.0-beta2, no message' => [
        'installed_version' => '8.2.0-beta2',
        'fixture' => 'sec.8.2.0_8.3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      '8.1.0-dev, no message' => [
        'installed_version' => '8.1.0-dev',
        'fixture' => 'sec.8.2.0_8.3.0-rc1',
        'requirements_section_heading' => '',
        'message' => '',
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have been released.
      '8.0.0, 9 unsupported' => [
        'installed_version' => '8.0.0',
        'fixture' => 'sec.8.2.0_9.0.0',
        'requirements_section_heading' => 'Errors found',
        'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
        'mock_date' => '',
      ],
      // Ensures the message is correct if the next major version has been
      // released and the additional minors indicated by
      // CORE_MINORS_WITH_SECURITY_COVERAGE minors have not been released.
      '8.2.0, 9 warning' => [
        'installed_version' => '8.2.0',
        'fixture' => 'sec.8.2.0_9.0.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 8.4.0 Update to 8.3 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
    ];

    // Drupal 10.5.x test cases.
    $test_cases += [
      // Ensure that a message is displayed during 10.5's active support.
      '10.5.0, supported' => [
        'installed_version' => '10.5.0',
        'fixture' => 'sec.10.6.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 2026-Jun-17 $release_coverage_message",
        'mock_date' => '2025-06-25',
      ],
      // Ensure a warning is displayed if less than six months remain until the
      // end of 10.5's security coverage.
      '10.5.0, supported, 6 months warn' => [
        'installed_version' => '10.5.0',
        'fixture' => 'sec.10.6.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 2026-Jun-17 $update_soon_message $release_coverage_message",
        'mock_date' => '2025-12-10',
      ],
    ];
    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['10.5.0, supported, last day warn'] = $test_cases['10.5.0, supported, 6 months warn'];
    $test_cases['10.5.0, supported, last day warn']['mock_date'] = '2026-06-16';

    // Ensure that if the 10.5 support window is finished a message is
    // displayed.
    $test_cases['10.5.0, support over'] = [
      'installed_version' => '10.5.0',
      'fixture' => 'sec.10.6.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
      'mock_date' => '2026-06-18',
    ];

    // Drupal 10.6 test cases.
    $test_cases['10.6.0, supported'] = [
      'installed_version' => '10.6.0',
      'fixture' => 'sec.10.6.0',
      'requirements_section_heading' => 'Checked',
      'message' => "Covered until 2026-Dec-09 $release_coverage_message",
      'mock_date' => '2026-01-01',
    ];
    // Ensure a warning is displayed if less than six months remain until the
    // end of 10.6's security coverage.
    $test_cases['10.6.0, supported, 6 months warn'] = [
      'installed_version' => '10.6.0',
      'fixture' => 'sec.10.6.0',
      'requirements_section_heading' => 'Warnings found',
      'message' => "Covered until 2026-Dec-09 $update_soon_message $release_coverage_message",
      'mock_date' => '2026-06-17',
    ];

    // Ensure that the message does not change, including on the last day of
    // security coverage.
    $test_cases['10.6.0, supported, last day warn'] = $test_cases['10.6.0, supported, 6 months warn'];
    $test_cases['10.6.0, supported, last day warn']['mock_date'] = '2026-12-08';

    // Ensure that if the support window is finished a message is displayed.
    $test_cases['10.6.0, support over'] = [
      'installed_version' => '10.6.0',
      'fixture' => 'sec.10.6.0',
      'requirements_section_heading' => 'Errors found',
      'message' => "$coverage_ended_message $update_asap_message $release_coverage_message",
      'mock_date' => '2026-12-10',
    ];

    // Drupal 11 test cases.
    $test_cases += [
      // Ensure the end dates for 10.5 and 10.6 only apply to major version 10.
      '11.6.0' => [
        'installed_version' => '11.6.0',
        'fixture' => 'sec.11.6.0',
        'requirements_section_heading' => 'Checked',
        'message' => "Covered until 11.8.0 $release_coverage_message",
        'mock_date' => '',
      ],
      '11.5.0' => [
        'installed_version' => '11.5.0',
        'fixture' => 'sec.11.6.0',
        'requirements_section_heading' => 'Warnings found',
        'message' => "Covered until 11.7.0 Update to 11.6 or higher soon to continue receiving security updates. $release_coverage_message",
        'mock_date' => '',
      ],
    ];
    return $test_cases;

  }

}
