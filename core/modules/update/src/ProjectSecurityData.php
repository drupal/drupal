<?php

namespace Drupal\update;

use Drupal\Core\Extension\ExtensionVersion;
use Drupal\Core\Utility\Error;

/**
 * Calculates a project's security coverage information.
 *
 * @internal
 *   This class implements logic to determine security coverage for Drupal core
 *   according to Drupal core security policy. It should not be called directly.
 */
final class ProjectSecurityData {

  /**
   * The number of minor versions of Drupal core that receive security coverage.
   *
   * For example, if this value is 2 and the existing version is 9.0.1, the
   * 9.0.x branch will receive security coverage until the release of version
   * 9.2.0.
   *
   * @todo In https://www.drupal.org/node/2998285 determine if we want this
   *   policy to be expressed in the updates.drupal.org feed, instead of relying
   *   on a hard-coded constant.
   *
   * @see https://www.drupal.org/core/release-cycle-overview
   */
  const CORE_MINORS_WITH_SECURITY_COVERAGE = 2;

  /**
   * Define constants for versions with security coverage end dates.
   *
   * Two types of constants are supported:
   * - SECURITY_COVERAGE_END_DATE_[VERSION_MAJOR]_[VERSION_MINOR]: A date in
   *   'Y-m-d' or 'Y-m' format.
   * - SECURITY_COVERAGE_ENDING_WARN_DATE_[VERSION_MAJOR]_[VERSION_MINOR]: A
   *   date in 'Y-m-d' format.
   *
   * @see \Drupal\update\ProjectSecurityRequirement::getDateEndRequirement()
   */
  const SECURITY_COVERAGE_END_DATE_10_5 = '2026-06-17';

  const SECURITY_COVERAGE_ENDING_WARN_DATE_10_5 = '2025-12-10';

  const SECURITY_COVERAGE_END_DATE_10_6 = '2026-12-09';

  const SECURITY_COVERAGE_ENDING_WARN_DATE_10_6 = '2026-06-17';

  /**
   * The existing (currently installed) version of the project.
   *
   * Because this class only handles the Drupal core project, values will be
   * semantic version numbers such as 8.8.0, 8.8.0-alpha1, or 9.0.0.
   *
   * @var string|null
   */
  protected $existingVersion;

  /**
   * Releases as returned by update_get_available().
   *
   * @var array
   *
   * @see update_get_available()
   */
  protected $releases;

  /**
   * Constructs a ProjectSecurityData object.
   *
   * @param string $existing_version
   *   The existing (currently installed) version of the project.
   * @param array $releases
   *   Project releases as returned by update_get_available().
   */
  private function __construct($existing_version = NULL, array $releases = []) {
    $this->existingVersion = $existing_version;
    $this->releases = $releases;
  }

  /**
   * Creates a ProjectSecurityData object from project data and releases.
   *
   * @param array $project_data
   *   Project data from Drupal\update\UpdateManagerInterface::getProjects() and
   *   processed by update_process_project_info().
   * @param array $releases
   *   Project releases as returned by update_get_available().
   *
   * @return static
   */
  public static function createFromProjectDataAndReleases(array $project_data, array $releases) {
    if (!($project_data['project_type'] === 'core' && $project_data['name'] === 'drupal')) {
      // Only Drupal core has an explicit coverage range.
      return new static();
    }
    return new static($project_data['existing_version'], $releases);
  }

  /**
   * Gets the security coverage information for a project.
   *
   * Currently only Drupal core is supported.
   *
   * @return array
   *   The security coverage information, or an empty array if no security
   *   information is available for the project. If security coverage is based
   *   on release of a specific version, the array will have the following
   *   keys:
   *   - security_coverage_end_version (string): The minor version the existing
   *     version will receive security coverage until.
   *   - additional_minors_coverage (int): The number of additional minor
   *     versions the existing version will receive security coverage.
   *   If the security coverage is based on a specific date, the array will have
   *   the following keys:
   *   - security_coverage_end_date (string): The month or date security
   *     coverage will end for the existing version. It can be in either
   *     'YYYY-MM' or 'YYYY-MM-DD' format.
   *   - (optional) security_coverage_ending_warn_date (string): The date, in
   *     the format 'YYYY-MM-DD', after which a warning should be displayed
   *     about upgrading to another version.
   */
  public function getCoverageInfo() {
    if (empty($this->releases[$this->existingVersion])) {
      // If the existing version does not have a release, we cannot get the
      // security coverage information.
      return [];
    }
    $info = [];
    $existing_release_version = ExtensionVersion::createFromVersionString($this->existingVersion);

    // Check if the installed version has a specific end date defined.
    $version_suffix = $existing_release_version->getMajorVersion() . '_' . $this->getSemanticMinorVersion($this->existingVersion);
    if (defined("self::SECURITY_COVERAGE_END_DATE_$version_suffix")) {
      $info['security_coverage_end_date'] = constant("self::SECURITY_COVERAGE_END_DATE_$version_suffix");
      $info['security_coverage_ending_warn_date'] =
        defined("self::SECURITY_COVERAGE_ENDING_WARN_DATE_$version_suffix")
          ? constant("self::SECURITY_COVERAGE_ENDING_WARN_DATE_$version_suffix")
          : NULL;
    }
    elseif ($security_coverage_until_version = $this->getSecurityCoverageUntilVersion()) {
      $info['security_coverage_end_version'] = $security_coverage_until_version;
      $info['additional_minors_coverage'] = $this->getAdditionalSecurityCoveredMinors($security_coverage_until_version);
    }
    return $info;
  }

  /**
   * Gets the release the current minor will receive security coverage until.
   *
   * For the sake of example, assume that the currently installed version of
   * Drupal is 8.7.11 and that static::CORE_MINORS_WITH_SECURITY_COVERAGE is 2.
   * When Drupal 8.9.0 is released, the supported minor versions will be 8.8
   * and 8.9. At that point, Drupal 8.7 will no longer have security coverage.
   * Therefore, this function would return "8.9.0".
   *
   * @todo In https://www.drupal.org/node/2998285 determine how we will know
   *    what the final minor release of a particular major version will be. This
   *    method should not return a version beyond that minor.
   *
   * @return string|null
   *   The version the existing version will receive security coverage until or
   *   NULL if this cannot be determined.
   */
  private function getSecurityCoverageUntilVersion() {
    $existing_release_version = ExtensionVersion::createFromVersionString($this->existingVersion);
    if (!empty($existing_release_version->getVersionExtra())) {
      // Only full releases receive security coverage.
      return NULL;
    }

    return $existing_release_version->getMajorVersion() . '.'
      . ($this->getSemanticMinorVersion($this->existingVersion) + static::CORE_MINORS_WITH_SECURITY_COVERAGE)
      . '.0';
  }

  /**
   * Gets the number of additional minor releases with security coverage.
   *
   * This function compares the currently installed (existing) version of
   * the project with two things:
   * - The latest available official release of that project.
   * - The target minor release where security coverage for the current release
   *   should expire. This target release is determined by
   *   getSecurityCoverageUntilVersion().
   *
   * For the sake of example, assume that the currently installed version of
   * Drupal is 8.7.11 and that static::CORE_MINORS_WITH_SECURITY_COVERAGE is 2.
   *
   * Before the release of Drupal 8.8.0, this function would return 2.
   *
   * After the release of Drupal 8.8.0 and before the release of 8.9.0, this
   * function would return 1 to indicate that the next minor version release
   * will end security coverage for 8.7.
   *
   * When Drupal 8.9.0 is released, this function would return 0 to indicate
   * that security coverage is over for 8.7.
   *
   * If the currently installed version is 9.0.0, and there is no 9.1.0 release
   * yet, the function would return 2. Once 9.1.0 is out, it would return 1.
   * When 9.2.0 is released, it would again return 0.
   *
   * Note: callers should not test this function's return value with empty()
   * since 0 is a valid return value that has different meaning than NULL.
   *
   * @param string $security_covered_version
   *   The version until which the existing version receives security coverage.
   *
   * @return int|null
   *   The number of additional minor releases that receive security coverage,
   *   or NULL if this cannot be determined.
   *
   * @see \Drupal\update\ProjectSecurityData\getSecurityCoverageUntilVersion()
   */
  private function getAdditionalSecurityCoveredMinors($security_covered_version) {
    $security_covered_version_major = ExtensionVersion::createFromVersionString($security_covered_version)->getMajorVersion();
    $security_covered_version_minor = $this->getSemanticMinorVersion($security_covered_version);
    foreach ($this->releases as $release_info) {
      try {
        $release = ProjectRelease::createFromArray($release_info);
      }
      catch (\UnexpectedValueException $exception) {
        // Ignore releases that are in an invalid format. Although this is
        // highly unlikely we should still process releases in the correct
        // format.
        Error::logException(\Drupal::logger('update'), $exception, 'Invalid project format: @release', ['@release' => print_r($release_info, TRUE)]);
        continue;
      }
      $release_version = ExtensionVersion::createFromVersionString($release->getVersion());
      if ($release_version->getMajorVersion() === $security_covered_version_major && $release->isPublished() && !$release_version->getVersionExtra()) {
        // The releases are ordered with the most recent releases first.
        // Therefore, if we have found a published, official release with the
        // same major version as $security_covered_version, then this release
        // can be used to determine the latest minor.
        $latest_minor = $this->getSemanticMinorVersion($release->getVersion());
        break;
      }
    }
    // If $latest_minor is set, we know that $security_covered_version_minor and
    // $latest_minor have the same major version. Therefore, we can subtract to
    // determine the number of additional minor releases with security coverage.
    return isset($latest_minor) ? $security_covered_version_minor - $latest_minor : NULL;
  }

  /**
   * Gets the minor version for a semantic version string.
   *
   * @param string $version
   *   The semantic version string.
   *
   * @return int
   *   The minor version as an integer.
   */
  private function getSemanticMinorVersion($version) {
    return (int) (explode('.', $version)[1]);
  }

}
