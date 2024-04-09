<?php

declare(strict_types=1);

namespace Drupal\Core\File;

/**
 * A flag for defining the behavior when dealing with existing files.
 */
enum FileExists {

  /* Appends a number until name is unique. */
  case Rename;
  /* Replace the existing file. */
  case Replace;
  /* Do nothing and return FALSE. */
  case Error;

  /**
   * Provide backwards compatibility with legacy integer values.
   *
   * @param int $legacyInt
   *   The legacy constant value from \Drupal\Core\File\FileSystemInterface.
   * @param string $methodName
   *   The method name for the deprecation message.
   *
   * @deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use
   *   \Drupal\Core\File\FileExists enum directly instead.
   *
   * @see https://www.drupal.org/node/3426517
   */
  public static function fromLegacyInt(int $legacyInt, string $methodName): self {
    @trigger_error("Passing the \$fileExists argument as an integer to $methodName() is deprecated in drupal:10.3.0 and is removed from drupal:12.0.0. Use \Drupal\Core\File\FileExists enum instead. See https://www.drupal.org/node/3426517", E_USER_DEPRECATED);
    return match ($legacyInt) {
      0 => FileExists::Rename,
      2 => FileExists::Error,
      default => FileExists::Replace,
    };
  }

}
