<?php

namespace Drupal\update;

/**
 * Provides a project release value object.
 */
final class ProjectRelease {

  /**
   * Whether the release is compatible with the site's Drupal core version.
   *
   * @var bool
   */
  private $isCoreCompatible;

  /**
   * The core compatibility message.
   *
   * @var string|null
   */
  protected $coreCompatibilityMessage;

  /**
   * The download URL.
   *
   * @var string
   */
  protected $downloadUrl;

  /**
   * The URL for the release.
   *
   * @var string
   */
  protected $url;

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
   * @param bool|null $is_core_compatible
   *   The core compatibility constraint.
   * @param string|null $core_compatibility_message
   *   The core compatibility message.
   * @param string $download_url
   *   The download URL.
   * @param string $url
   *   The URL for the release.
   */
  private function __construct(?array $release_types, string $status, string $version, ?string $date, ?bool $is_core_compatible, ?string $core_compatibility_message, string $download_url, string $url) {
    $this->releaseTypes = $release_types;
    $this->status = $status;
    $this->version = $version;
    $this->date = $date;
    $this->isCoreCompatible = $is_core_compatible;
    $this->coreCompatibilityMessage = $core_compatibility_message;
    $this->downloadUrl = $download_url;
    $this->url = $url;
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
      $release_data['date'] ?? NULL,
      $release_data['core_compatible'] ?? NULL,
      $release_data['core_compatibility_message'] ?? NULL,
      $release_data['download_link'],
      $release_data['link']
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

  /**
   * Gets the releases core compatibility Composer constraint.
   *
   * @return bool|null
   *   Whether the release is compatible or NULL if no data is set.
   */
  public function isCoreCompatible(): ?bool {
    return $this->isCoreCompatible;
  }

  /**
   * Gets the core compatibility message for the site's version of Drupal core.
   *
   * @return string
   *   The core compatibility message or NULL if none is available.
   */
  public function getCoreCompatibilityMessage(): ?string {
    return $this->coreCompatibilityMessage;
  }

  /**
   * Gets the download URL of the release.
   *
   * @return string
   *   The download URL.
   */
  public function getDownloadUrl(): string {
    return $this->downloadUrl;
  }

  /**
   * Gets the URL of the release.
   *
   * @return string
   *   The URL of the release.
   */
  public function getUrl(): string {
    return $this->url;
  }

}
