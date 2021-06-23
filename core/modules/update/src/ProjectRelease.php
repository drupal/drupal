<?php

namespace Drupal\update;

use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;

/**
 * Provides a project release value object.
 */
final class ProjectRelease {

  /**
   * Whether the release is compatible with the site's Drupal core version.
   *
   * @var bool
   */
  private $coreCompatible;

  /**
   * The core compatibility message or NULL if not set.
   *
   * @var string|null
   */
  private $coreCompatibilityMessage;

  /**
   * The download URL or NULL if none is available.
   *
   * @var string|null
   */
  private $downloadUrl;

  /**
   * The URL for the release.
   *
   * @var string
   */
  private $releaseUrl;

  /**
   * The release types or NULL if not set.
   *
   * @var string[]|null
   */
  private $releaseTypes;

  /**
   * Whether the release is published.
   *
   * @var bool
   */
  private $published;

  /**
   * The release version.
   *
   * @var string
   */
  private $version;

  /**
   * The release date as a Unix timestamp or NULL if no date was set.
   *
   * @var int|null
   */
  private $date;

  /**
   * Constructs a ProjectRelease object.
   *
   * @param bool $published
   *   Whether the release is published.
   * @param string $version
   *   The release version.
   * @param string $release_url
   *   The URL for the release.
   * @param string[]|null $release_types
   *   The release types or NULL if not set.
   * @param bool|null $core_compatible
   *   Whether the release is compatible with the site's version of Drupal core.
   * @param string|null $core_compatibility_message
   *   The core compatibility message or NULL if not set.
   * @param string|null $download_url
   *   The download URL or NULL if not available.
   * @param int|null $date
   *   The release date in Unix timestamp format.
   */
  private function __construct(bool $published, string $version, string $release_url, ?array $release_types, ?bool $core_compatible, ?string $core_compatibility_message, ?string $download_url, ?int $date) {
    $this->published = $published;
    $this->version = $version;
    $this->releaseUrl = $release_url;
    $this->releaseTypes = $release_types;
    $this->coreCompatible = $core_compatible;
    $this->coreCompatibilityMessage = $core_compatibility_message;
    $this->downloadUrl = $download_url;
    $this->date = $date;
  }

  /**
   * Creates a ProjectRelease instance from an array.
   *
   * @param array $release_data
   *   The project release data as returned by update_get_available().
   *
   * @return \Drupal\update\ProjectRelease
   *   The ProjectRelease instance.
   *
   * @throws \UnexpectedValueException
   *   Thrown if project release data is not valid.
   *
   * @see \update_get_available()
   */
  public static function createFromArray(array $release_data): ProjectRelease {
    static::validateReleaseData($release_data);
    return new ProjectRelease(
      $release_data['status'] === 'published',
      $release_data['version'],
      $release_data['release_link'],
      $release_data['terms']['Release type'] ?? NULL,
      $release_data['core_compatible'] ?? NULL,
      $release_data['core_compatibility_message'] ?? NULL,
      $release_data['download_link'] ?? NULL,
      $release_data['date'] ?? NULL
    );
  }

  /**
   * Validates the project release data.
   *
   * @param array $data
   *   The project release data.
   *
   * @throws \UnexpectedValueException
   *   Thrown if project release data is not valid.
   */
  private static function validateReleaseData(array $data): void {
    $not_blank_constraints = [
      new Type('string'),
      new NotBlank(),
    ];
    $collection_constraint = new Collection([
      'fields' => [
        'version' => $not_blank_constraints,
        'date' => new Optional([new Type('numeric')]),
        'core_compatible' => new Optional([new Type('boolean')]),
        'core_compatibility_message' => new Optional($not_blank_constraints),
        'status' => new Choice(['published', 'unpublished']),
        'download_link' => new Optional($not_blank_constraints),
        'release_link' => $not_blank_constraints,
        'terms' => new Optional([
          new Type('array'),
          new Collection([
            'Release type' => new Optional([
              new Type('array'),
            ]),
          ]),
        ]),
      ],
      'allowExtraFields' => TRUE,
    ]);
    $violations = Validation::createValidator()->validate($data, $collection_constraint);
    if (count($violations)) {
      foreach ($violations as $violation) {
        $violation_messages[] = "Field " . $violation->getPropertyPath() . ": " . $violation->getMessage();
      }
      throw new \UnexpectedValueException('Malformed release data: ' . implode(",\n", $violation_messages));
    }
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
   * @return int|null
   *   The date of the release or null if no date is available.
   */
  public function getDate(): ?int {
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
  public function isInsecure(): bool {
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
    return $this->releaseTypes && in_array($type, $this->releaseTypes, TRUE);
  }

  /**
   * Determines if the release is published.
   *
   * @return bool
   *   TRUE if the release is published, or FALSE otherwise.
   */
  public function isPublished(): bool {
    return $this->published;
  }

  /**
   * Determines whether release is compatible the site's version of Drupal core.
   *
   * @return bool|null
   *   Whether the release is compatible or NULL if no data is set.
   */
  public function isCoreCompatible(): ?bool {
    return $this->coreCompatible;
  }

  /**
   * Gets the core compatibility message for the site's version of Drupal core.
   *
   * @return string|null
   *   The core compatibility message or NULL if none is available.
   */
  public function getCoreCompatibilityMessage(): ?string {
    return $this->coreCompatibilityMessage;
  }

  /**
   * Gets the download URL of the release.
   *
   * @return string|null
   *   The download URL or NULL if none is available.
   */
  public function getDownloadUrl(): ?string {
    return $this->downloadUrl;
  }

  /**
   * Gets the URL of the release.
   *
   * @return string
   *   The URL of the release.
   */
  public function getReleaseUrl(): string {
    return $this->releaseUrl;
  }

}
