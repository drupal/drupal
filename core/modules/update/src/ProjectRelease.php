<?php

namespace Drupal\update;

/**
 * Provides a project release value object.
 */
final class ProjectRelease {

  /**
   * The release types for the release.
   *
   * @var array|null
   */
  private $releaseTypes = [];

  /**
   * The status of the release.
   *
   * @var string
   */
  private $status;

  /**
   * The release version.
   *
   * @var string
   */
  private $version;

  /**
   * The date of release.
   *
   * @var string|null
   */
  private $date;

  /**
   * Constructs a ProjectRelease object.
   *
   * @param string[]|null $release_types
   *   The release types.
   * @param string $status
   *   The release status.
   * @param string $version
   *   The release version.
   * @param string|null $date
   *   The release date.
   */
  private function __construct(?array $release_types, string $status, string $version, ?string $date) {
    $this->releaseTypes = $release_types;
    $this->status = $status;
    $this->version = $version;
    $this->date = $date;
  }

  /**
   * Creates a ProjectRelease from an array.
   *
   * @param array $release_data
   *   The project release data.
   *
   * @return \Drupal\update\ProjectRelease
   *   The ProjectRelease instance.
   */
  public static function createFromArray(array $release_data): ProjectRelease {
    return new ProjectRelease(
      $release_data['terms']['Release type'] ?? NULL,
      $release_data['status'],
      $release_data['version'] ?? NULL,
      $release_data['date'] ?? NULL
    );
  }

  /**
   * Gets the project version.
   *
   * @return string
   *   The project version.
   */
  public function getVersion(): string {
    return $this->version;
  }

  /**
   * Gets the release date if set.
   *
   * @return string|null
   *   The date of the release or null if no date is available.
   */
  public function getDate(): ?string {
    return $this->date;
  }

  /**
   * Determines if the release is a security release.
   *
   * @return bool
   *   TRUE if the release is security release, or FALSE otherwise.
   */
  public function isSecurityRelease(): bool {
    return $this->isReleaseType('Security update');
  }

  /**
   * Determines if the release is unsupported.
   *
   * @return bool
   *   TRUE if the release is unsupported, or FALSE otherwise.
   */
  public function isUnsupported(): bool {
    return $this->isReleaseType('Unsupported');
  }

  /**
   * Determines if the release is insecure.
   *
   * @return bool
   *   TRUE if the release is insecure, or FALSE otherwise.
   */
  public function isInSecure(): bool {
    return $this->isReleaseType('Insecure');
  }

  /**
   * Determines if the release is matches a type.
   *
   * @param string $type
   *   The release type.
   *
   * @return bool
   *   TRUE if the release matches the type, or FALSE otherwise.
   */
  private function isReleaseType(string $type): bool {
    return $this->releaseTypes && in_array($type, $this->releaseTypes);
  }

  /**
   * Determines if the release is unpublished.
   *
   * @return bool
   *   TRUE if the release is unpublished, or FALSE otherwise.
   */
  public function isUnpublished(): bool {
    return $this->status === 'unpublished';
  }

}
