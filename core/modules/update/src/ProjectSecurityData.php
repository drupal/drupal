<?php

namespace Drupal\update;

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
  const SECURITY_COVERAGE_END_DATE_8_8 = '2020-12-02';

  const SECURITY_COVERAGE_ENDING_WARN_DATE_8_8 = '2020-06-02';

  const SECURITY_COVERAGE_END_DATE_8_9 = '2021-11';

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
   * Each release item in the array has metadata about that release. This class
   * uses the keys:
   * - status (string): The status of the release.
   * - version (string): The version number of the release.
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
    $existing_release_version = ModuleVersion::createFromVersionString($this->existingVersion);

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
   * @todo In https://www.drupal.org/node/2998285 determine how we will know
   *    what the final minor release of a particular major version will be. This
   *    method should not return a version beyond that minor.
   *
   * @return string|null
   *   The version the existing version will receive security coverage until or
   *   NULL if this cannot be determined.
   */
  private function getSecurityCoverageUntilVersion() {
    $existing_release_version = ModuleVersion::createFromVersionString($this->existingVersion);
    if (!empty($existing_release_version->getVersionExtra())) {
      // Only full releases receive security coverage.
      return NULL;
    }

    return $existing_release_version->getMajorVersion() . '.'
      . ($this->getSemanticMinorVersion($this->existingVersion) + static::CORE_MINORS_WITH_SECURITY_COVERAGE)
      . '.0';
  }

  /**
   * Gets the number of additional minor security covered releases.
   *
   * @param string $security_covered_version
   *   The version until which the existing version receives security coverage.
   *
   * @return int|null
   *   The number of additional minor releases that receive security coverage,
   *   or NULL if this cannot be determined.
   */
  private function getAdditionalSecurityCoveredMinors($security_covered_version) {
    $security_covered_version_major = ModuleVersion::createFromVersionString($security_covered_version)->getMajorVersion();
    $security_covered_version_minor = $this->getSemanticMinorVersion($security_covered_version);
    foreach ($this->releases as $release) {
      $release_version = ModuleVersion::createFromVersionString($release['version']);
      if ($release_version->getMajorVersion() === $security_covered_version_major && $release['status'] === 'published' && !$release_version->getVersionExtra()) {
        // The releases are ordered with the most recent releases first.
        // Therefore if we have found an official, published release with the
        // same major version as $security_covered_version then this release
        // can be used to determine the latest minor.
        $latest_minor = $this->getSemanticMinorVersion($release['version']);
        break;
      }
    }
    // If $latest_minor is set, we know that $latest_minor and
    // $security_covered_version_minor have the same major version. Therefore we
    // can simply subtract to determine the number of additional minor security
    // covered releases.
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
