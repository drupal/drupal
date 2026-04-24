<?php

namespace Drupal\locale\File;

use Drupal\Core\Language\LanguageInterface;
use Drupal\locale\LocaleSource;

/**
 * Defines the locale file value object.
 */
final class LocaleFile {

  /**
   * The type of file, local, remote or current.
   *
   * @var string
   */
  public string $type;

  /**
   * The directory the file is in.
   *
   * @var string
   */
  public string $directory;

  /**
   * When the translation was last checked.
   *
   * @var string|null
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public int $last_checked;

  /**
   * Creates a LocaleFile object for tracking translation information.
   *
   * @param string $filename
   *   The filename.
   * @param string $uri
   *   The uri of the file.
   * @param string $hash
   *   The hash using the LocaleSource::LOCAL_FILE_HASH_ALGO.
   * @param int|null $timestamp
   *   The filemtime of the uri.
   * @param string|null $langcode
   *   The langcode the translation is for.
   * @param string|null $project
   *   The project the translation is for.
   * @param string|null $version
   *   The project version the translation is for.
   */
  public function __construct(
    public string $filename,
    public string $uri,
    public string $hash,
    public ?int $timestamp = NULL,
    public ?string $langcode = NULL,
    public ?string $project = NULL,
    public ?string $version = NULL,
  ) {}

  /**
   * Creates a LocaleFile from the filepath.
   *
   * @param string $filename
   *   The filename of a file to import.
   * @param string $filepath
   *   The filepath of a file to import.
   * @param string|null $langcodeOverride
   *   The language code. Overrides the file language.
   *
   * @return self
   *   A LocaleFile.
   */
  public static function createFromPath(string $filename, string $filepath, ?string $langcodeOverride = NULL): self {
    $project = NULL;
    $version = NULL;
    $hash = hash_file(LocaleSource::LOCAL_FILE_HASH_ALGO, $filepath);

    // An attempt is made to determine the translation language, project name
    // and project version from the file name. Supported file name patterns
    // are: {project}-{version}.{langcode}.po, {prefix}.{langcode}.po or
    // {langcode}.po. Alternatively the translation language can be set using
    // the $langcodeOverride.
    // Extract project, version and language code from the file name. Supported:
    // "{project}-{version}.{langcode}.po", "{prefix}.{langcode}.po" or
    // "{langcode}.po".
    preg_match('!
    (                       # project OR project and version OR empty (group 1)
      ([a-z_]+)             # project name (group 2)
      \.                    # .
      |                     # OR
      ([a-z_]+)             # project name (group 3)
      \-                    # -
      ([0-9a-z\.\-\+]+)     # version (group 4)
      \.                    # .
      |                     # OR
    )                       # (empty)
    ([^\./]+)               # language code (group 5)
    \.                      # .
    po                      # po extension
    $!x', $filename, $matches);
    if (isset($matches[5])) {
      $project = $matches[2] . $matches[3];
      $version = $matches[4];
      $langcode = $langcodeOverride ?? $matches[5];
    }
    else {
      $langcode = $langcodeOverride ?? LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }
    return new self($filename, $filepath, $hash, filemtime($filepath), $langcode, $project, $version);
  }

}
