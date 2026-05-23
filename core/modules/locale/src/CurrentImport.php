<?php

namespace Drupal\locale;

use Drupal\locale\File\LocaleFile;

/**
 * Represents information about the current import status.
 *
 * @internal
 */
final class CurrentImport {

  /**
   * Creates a CurrentImport object for tracking translation information.
   *
   * @param string $project
   *   The project the translation is for.
   * @param string $langcode
   *   The langcode the translation is for.
   * @param string $version
   *   The project version the translation is for.
   * @param string $hash
   *   The hash using the LocaleSource::LOCAL_FILE_HASH_ALGO.
   * @param int $timestamp
   *   When the translation was imported, 0 means never.
   * @param int|null $last_checked
   *   When the translation was last checked.
   */
  public function __construct(
    public string $project,
    public string $langcode,
    public string $version,
    public string $hash,
    public int $timestamp,
    public ?int $last_checked = NULL,
  ) {}

  /**
   * Creates a CurrentImport from a LocaleFile.
   *
   * @param \Drupal\locale\File\LocaleFile $file
   *   The LocaleFile containing the primer information.
   *
   * @return self
   *   The CurrentImport object.
   */
  public static function createFromFile(LocaleFile $file): self {
    $currentImport = new CurrentImport($file->project, $file->langcode, $file->version, $file->hash, $file->timestamp, $file->last_checked ?? NULL);
    return $currentImport;
  }

  /**
   * Creates a CurrentImport from a source object.
   *
   * @param object $source
   *   The source object containing the primer information.
   *
   * @return self
   *   The CurrentImport object.
   */
  public static function createFromSource(object $source): self {
    $currentImport = new CurrentImport($source->project, $source->langcode, $source->version, $source->hash, $source->timestamp, $source->last_checked ?? NULL);
    return $currentImport;
  }

  /**
   * Creates a CurrentImport from an array.
   *
   * @param array $result
   *   The array containing the primer information.
   *
   * @return self
   *   The CurrentImport object.
   */
  public static function createFromArray(array $result): self {
    $currentImport = new CurrentImport($result['project'], $result['langcode'], $result['version'], $result['hash'], $result['timestamp'], $result['last_checked'] ?? NULL);
    return $currentImport;
  }

}
