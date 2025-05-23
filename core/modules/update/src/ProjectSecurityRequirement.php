<?php

namespace Drupal\update;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Class for generating a project's security requirement.
 *
 * @see update_requirements()
 *
 * @internal
 *   This class implements logic to determine security coverage for Drupal core
 *   according to Drupal core security policy. It should not be called directly.
 */
final class ProjectSecurityRequirement {

  use StringTranslationTrait;

  /**
   * The project title.
   *
   * @var string|null
   */
  protected $projectTitle;

  /**
   * Security coverage information for the project.
   *
   * @var array
   *
   * @see \Drupal\update\ProjectSecurityData::getCoverageInfo()
   */
  private $securityCoverageInfo;

  /**
   * The next version after the installed version in the format [MAJOR].[MINOR].
   *
   * @var string|null
   */
  private $nextMajorMinorVersion;

  /**
   * The existing (currently installed) version in the format [MAJOR].[MINOR].
   *
   * @var string|null
   */
  private $existingMajorMinorVersion;

  /**
   * Constructs a ProjectSecurityRequirement object.
   *
   * @param string|null $project_title
   *   The project title.
   * @param array $security_coverage_info
   *   Security coverage information as set by
   *   \Drupal\update\ProjectSecurityData::getCoverageInfo().
   * @param string|null $existing_major_minor_version
   *   The existing (currently installed) version in the format [MAJOR].[MINOR].
   * @param string|null $next_major_minor_version
   *   The next version after the installed version in the format
   *   [MAJOR].[MINOR].
   */
  private function __construct($project_title = NULL, array $security_coverage_info = [], $existing_major_minor_version = NULL, $next_major_minor_version = NULL) {
    $this->projectTitle = $project_title;
    $this->securityCoverageInfo = $security_coverage_info;
    $this->existingMajorMinorVersion = $existing_major_minor_version;
    $this->nextMajorMinorVersion = $next_major_minor_version;
  }

  /**
   * Creates a ProjectSecurityRequirement object from project data.
   *
   * @param array $project_data
   *   Project data from Drupal\update\UpdateManagerInterface::getProjects().
   *   The 'security_coverage_info' key should be set by
   *   calling \Drupal\update\ProjectSecurityData::getCoverageInfo() before
   *   calling this method. The following keys are used in this method:
   *   - existing_version (string): The version of the project that is installed
   *     on the site.
   *   - project_type (string): The type of project.
   *   - name (string): The project machine name.
   *   - title (string): The project title.
   * @param array $security_coverage_info
   *   The security coverage information as returned by
   *   \Drupal\update\ProjectSecurityData::getCoverageInfo().
   *
   * @return static
   *
   * @see \Drupal\update\UpdateManagerInterface::getProjects()
   * @see \Drupal\update\ProjectSecurityData::getCoverageInfo()
   * @see update_process_project_info()
   */
  public static function createFromProjectDataAndSecurityCoverageInfo(array $project_data, array $security_coverage_info) {
    if ($project_data['project_type'] !== 'core' || $project_data['name'] !== 'drupal' || empty($security_coverage_info)) {
      return new static();
    }
    if (isset($project_data['existing_version'])) {
      [$major, $minor] = explode('.', $project_data['existing_version']);
      $existing_version = "$major.$minor";
      $next_version = "$major." . ((int) $minor + 1);
      return new static($project_data['title'], $security_coverage_info, $existing_version, $next_version);
    }
    return new static($project_data['title'], $security_coverage_info);
  }

  /**
   * Gets the security coverage requirement, if any.
   *
   * @return array
   *   Requirements array as specified by hook_requirements(), or an empty array
   *   if no requirements can be determined.
   */
  public function getRequirement() {
    if (isset($this->securityCoverageInfo['security_coverage_end_version'])) {
      $requirement = $this->getVersionEndRequirement();
    }
    elseif (isset($this->securityCoverageInfo['security_coverage_end_date'])) {
      $requirement = $this->getDateEndRequirement();
    }
    else {
      return [];
    }
    $requirement['title'] = $this->t('Drupal core security coverage');
    return $requirement;
  }

  /**
   * Gets the requirements based on security coverage until a specific version.
   *
   * @return array
   *   Requirements array as specified by hook_requirements().
   */
  private function getVersionEndRequirement() {
    $requirement = [];
    if ($security_coverage_message = $this->getVersionEndCoverageMessage()) {
      $requirement['description'] = $security_coverage_message;
      if ($this->securityCoverageInfo['additional_minors_coverage'] > 0) {
        $requirement['value'] = $this->t(
          'Covered until @end_version',
          ['@end_version' => $this->securityCoverageInfo['security_coverage_end_version']]
        );
        $requirement['severity'] = $this->securityCoverageInfo['additional_minors_coverage'] > 1 ? RequirementSeverity::Info : RequirementSeverity::Warning;
      }
      else {
        $requirement['value'] = $this->t('Coverage has ended');
        $requirement['severity'] = RequirementSeverity::Error;
      }
    }
    return $requirement;
  }

  /**
   * Gets the message for additional minor version security coverage.
   *
   * @return array[]
   *   A render array containing security coverage message.
   *
   * @see \Drupal\update\ProjectSecurityData::getCoverageInfo()
   */
  private function getVersionEndCoverageMessage() {
    if ($this->securityCoverageInfo['additional_minors_coverage'] > 0) {
      // If the installed minor version will receive security coverage until
      // newer minor versions are released, inform the user.
      if ($this->securityCoverageInfo['additional_minors_coverage'] === 1) {
        // If the installed minor version will only receive security coverage
        // for 1 newer minor core version, encourage the site owner to update
        // soon.
        $message['coverage_message'] = [
          '#markup' => $this->t(
            '<a href=":update_status_report">Update to @next_minor or higher</a> soon to continue receiving security updates.',
            [
              ':update_status_report' => Url::fromRoute('update.status')->toString(),
              '@next_minor' => $this->nextMajorMinorVersion,
            ]
          ),
          '#suffix' => ' ',
        ];
      }
    }
    else {
      // Because the current minor version no longer has security coverage,
      // advise the site owner to update.
      $message['coverage_message'] = [
        '#markup' => $this->getVersionNoSecurityCoverageMessage(),
        '#suffix' => ' ',
      ];
    }
    $message['release_cycle_link'] = [
      '#markup' => $this->getReleaseCycleLink(),
    ];
    return $message;
  }

  /**
   * Gets the security coverage requirement based on an end date.
   *
   * @return array
   *   Requirements array as specified by hook_requirements().
   */
  private function getDateEndRequirement() {
    $requirement = [];
    /** @var \Drupal\Component\Datetime\Time $time */
    $time = \Drupal::service('datetime.time');
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    // 'security_coverage_end_date' will either be in format 'Y-m-d' or 'Y-m'.
    if (substr_count($this->securityCoverageInfo['security_coverage_end_date'], '-') === 2) {
      $date_format = 'Y-m-d';
      $full_security_coverage_end_date = $this->securityCoverageInfo['security_coverage_end_date'];
    }
    else {
      $date_format = 'Y-m';
      // If the date does not include a day, use '15'. When calling
      // \DateTime::createFromFormat() the current day will be used if one is
      // not provided. This may cause the month to be wrong at the beginning or
      // end of the month. '15' will never be displayed because we are using the
      // 'Y-m' format.
      $full_security_coverage_end_date = $this->securityCoverageInfo['security_coverage_end_date'] . '-15';
    }

    $comparable_request_date = $date_formatter->format($time->getRequestTime(), 'custom', $date_format);
    if ($this->securityCoverageInfo['security_coverage_end_date'] <= $comparable_request_date) {
      // Security coverage is over.
      $requirement['value'] = $this->t('Coverage has ended');
      $requirement['severity'] = RequirementSeverity::Error;
      $requirement['description']['coverage_message'] = [
        '#markup' => $this->getVersionNoSecurityCoverageMessage(),
        '#suffix' => ' ',
      ];
    }
    else {
      $security_coverage_end_timestamp = \DateTime::createFromFormat('Y-m-d', $full_security_coverage_end_date)->getTimestamp();
      $output_date_format = $date_format === 'Y-m-d' ? 'Y-M-d' : 'Y-M';
      $formatted_end_date = $date_formatter
        ->format($security_coverage_end_timestamp, 'custom', $output_date_format);
      $translation_arguments = ['@date' => $formatted_end_date];
      $requirement['value'] = $this->t('Covered until @date', $translation_arguments);
      $requirement['severity'] = RequirementSeverity::Info;
      // 'security_coverage_ending_warn_date' will always be in the format
      // 'Y-m-d'.
      $request_date = $date_formatter->format($time->getRequestTime(), 'custom', 'Y-m-d');
      if (!empty($this->securityCoverageInfo['security_coverage_ending_warn_date']) && $this->securityCoverageInfo['security_coverage_ending_warn_date'] <= $request_date) {
        $requirement['description']['coverage_message'] = [
          '#markup' => $this->t('Update to a supported version soon to continue receiving security updates.'),
          '#suffix' => ' ',
        ];
        $requirement['severity'] = RequirementSeverity::Warning;
      }
    }
    $requirement['description']['release_cycle_link'] = ['#markup' => $this->getReleaseCycleLink()];
    return $requirement;
  }

  /**
   * Gets the formatted message for a project with no security coverage.
   *
   * @return string
   *   The message for a version with no security coverage.
   */
  private function getVersionNoSecurityCoverageMessage() {
    return $this->t(
      '<a href=":update_status_report">Update to a supported minor</a> as soon as possible to continue receiving security updates.',
      [':update_status_report' => Url::fromRoute('update.status')->toString()]
    );
  }

  /**
   * Gets a link the release cycle page on drupal.org.
   *
   * @return string
   *   A link to the release cycle page on drupal.org.
   */
  private function getReleaseCycleLink() {
    return $this->t(
        'Visit the <a href=":url">release cycle overview</a> for more information on supported releases.',
        [':url' => 'https://www.drupal.org/core/release-cycle-overview']
      );
  }

}
