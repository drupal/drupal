<?php

namespace Drupal\update;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Utility class to set core compatibility messages for project releases.
 *
 * @internal
 *   This class implements logic used by update_calculate_project_status(). It
 *   should not be called directly.
 */
final class ProjectCoreCompatibility {

  use StringTranslationTrait;

  /**
   * The currently installed version of Drupal core.
   *
   * @var string
   */
  protected $existingCoreVersion;

  /**
   * Cache of core versions that are available for updates.
   *
   * @var string[]
   */
  protected $possibleCoreUpdateVersions;

  /**
   * Cache of core compatibility messages per core version constraint.
   *
   * Keys are core version constraint strings, values are human-readable
   * messages about the versions of core that version constraint maps to.
   *
   * This list is cached since many project releases will use the same core
   * compatibility constraint.
   *
   * @var string[]
   */
  protected $compatibilityMessages = [];

  /**
   * Constructs a ProjectCoreCompatibility object.
   *
   * @param array $core_data
   *   The project data for Drupal core as returned by
   *   \Drupal\update\UpdateManagerInterface::getProjects() and then processed
   *   by update_process_project_info() and
   *   update_calculate_project_update_status().
   * @param array $core_releases
   *   The Drupal core available releases.
   *
   * @see \Drupal\update\UpdateManagerInterface::getProjects()
   * @see update_process_project_info()
   * @see update_calculate_project_update_status()
   */
  public function __construct(array $core_data, array $core_releases) {
    if (isset($core_data['existing_version'])) {
      $this->existingCoreVersion = $core_data['existing_version'];
      $this->possibleCoreUpdateVersions = $this->getPossibleCoreUpdateVersions($core_releases);
    }
  }

  /**
   * Gets the core versions that should be considered for compatibility ranges.
   *
   * @param array $core_releases
   *   The Drupal core available releases.
   *
   * @return string[]
   *   The core version numbers that are possible to update the site to.
   */
  protected function getPossibleCoreUpdateVersions(array $core_releases) {
    if (!isset($core_releases[$this->existingCoreVersion])) {
      // If we can't determine the existing version of core then we can't
      // calculate the core compatibility of a given release based on core
      // versions after the existing version.
      return [];
    }
    $core_release_versions = array_keys($core_releases);
    $possible_core_update_versions = Semver::satisfiedBy($core_release_versions, '>= ' . $this->existingCoreVersion);
    $possible_core_update_versions = Semver::sort($possible_core_update_versions);
    $possible_core_update_versions = array_filter($possible_core_update_versions, function ($version) {
      return VersionParser::parseStability($version) === 'stable';
    });
    return $possible_core_update_versions;
  }

  /**
   * Sets core compatibility messages for project releases.
   *
   * @param array &$project_data
   *   The project data as returned by
   *   \Drupal\update\UpdateManagerInterface::getProjects() and then processed
   *   by update_process_project_info() and
   *   update_calculate_project_update_status(). If set, the following keys are
   *   used in this method:
   *   - recommended (string): A project version number.
   *   - latest_version (string): A project version number.
   *   - also (string[]): Project version numbers.
   *   - releases (array[]): An array where the keys are project version numbers
   *     and the values are arrays of project release information.
   *   - security updates (array[]): An array of project release information.
   *
   * @see \Drupal\update\UpdateManagerInterface::getProjects()
   * @see update_process_project_info()
   * @see update_calculate_project_update_status()
   */
  public function setReleaseMessage(array &$project_data) {
    if (empty($this->possibleCoreUpdateVersions)) {
      return;
    }

    // Get the various releases that will need to have core compatibility
    // messages added to them.
    $releases_to_set = [];
    $versions = [];
    if (!empty($project_data['recommended'])) {
      $versions[] = $project_data['recommended'];
    }
    if (!empty($project_data['latest_version'])) {
      $versions[] = $project_data['latest_version'];
    }
    if (!empty($project_data['also'])) {
      $versions = array_merge($versions, $project_data['also']);
    }
    foreach ($versions as $version) {
      if (isset($project_data['releases'][$version])) {
        $releases_to_set[] = &$project_data['releases'][$version];
      }
    }
    if (!empty($project_data['security updates'])) {
      foreach ($project_data['security updates'] as &$security_update) {
        $releases_to_set[] = &$security_update;
      }
    }
    foreach ($releases_to_set as &$release) {
      if (!empty($release['core_compatibility'])) {
        $release['core_compatible'] = $this->isCoreCompatible($release['core_compatibility']);
        $release['core_compatibility_message'] = $this->createMessageFromCoreCompatibility($release['core_compatibility']);
      }
    }
  }

  /**
   * Determines if a release is compatible with the currently installed core.
   *
   * @param string $core_compatibility_constraint
   *   A semantic version constraint.
   *
   * @return bool
   *   TRUE if the given constraint is satisfied by the currently installed
   *   version of Drupal core, otherwise FALSE.
   */
  protected function isCoreCompatible($core_compatibility_constraint) {
    return Semver::satisfies($this->existingCoreVersion, $core_compatibility_constraint);
  }

  /**
   * Creates core a compatibility message from a semantic version constraint.
   *
   * @param string $core_compatibility_constraint
   *   A semantic version constraint.
   *
   * @return string
   *   The core compatibility message.
   */
  protected function createMessageFromCoreCompatibility($core_compatibility_constraint) {
    if (!isset($this->compatibilityMessages[$core_compatibility_constraint])) {
      $core_compatibility_ranges = $this->getCompatibilityRanges($core_compatibility_constraint);
      $range_messages = [];
      foreach ($core_compatibility_ranges as $core_compatibility_range) {
        if (count($core_compatibility_range) === 2) {
          $range_messages[] = $this->t('@low_version_number to @high_version_number', ['@low_version_number' => $core_compatibility_range[0], '@high_version_number' => $core_compatibility_range[1]]);
        }
        else {
          $range_messages[] = $core_compatibility_range[0];
        }
      }
      $this->compatibilityMessages[$core_compatibility_constraint] = $this->t('Requires Drupal core:') . ' ' . implode(', ', $range_messages);
    }
    return $this->compatibilityMessages[$core_compatibility_constraint];
  }

  /**
   * Gets the compatibility ranges for a semantic version constraint.
   *
   * @param string $core_compatibility_constraint
   *   A semantic version constraint.
   *
   * @return array[]
   *   An array compatibility ranges. If a range array has 2 elements then this
   *   denotes a range of compatibility between and including the 2 versions. If
   *   the range has 1 element then it denotes compatibility with a single
   *   version.
   */
  protected function getCompatibilityRanges($core_compatibility_constraint) {
    $compatibility_ranges = [];
    foreach ($this->possibleCoreUpdateVersions as $possible_core_update_version) {
      if (Semver::satisfies($possible_core_update_version, $core_compatibility_constraint)) {
        if (empty($range)) {
          $range[] = $possible_core_update_version;
        }
        else {
          $range[1] = $possible_core_update_version;
        }
      }
      else {
        // If core version does not satisfy the constraint and there is a non
        // empty range, add it to the list of ranges.
        if (!empty($range)) {
          $compatibility_ranges[] = $range;
          // Start a new range.
          $range = [];
        }
      }
    }
    if (!empty($range)) {
      $compatibility_ranges[] = $range;
    }
    return $compatibility_ranges;
  }

}
