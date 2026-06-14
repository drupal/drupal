<?php

namespace Drupal\locale;

/**
 * Provides source and translation status information for a project/langcode.
 *
 *    - "project": Project name.
 *    - "name": Project name (inherited from project).
 *    - "language": Language code.
 *    - "core": Core version (inherited from project).
 *    - "version": Project version (inherited from project).
 *    - "project_type": Project type (inherited from project).
 *    - "files": Array of file objects containing properties of local and
 *      remote translation files.
 *    Other processes can add the following properties:
 *    - "type": Most recent translation source found. LOCALE_TRANSLATION_REMOTE
 *       and LOCALE_TRANSLATION_LOCAL indicate available new translations,
 *       LOCALE_TRANSLATION_CURRENT indicate that the current translation is
 *       them most recent. "type" corresponds with a key of the "files" array.
 *    - "timestamp": The creation time of the "type" translation (file).
 *    - "last_checked": The time when the "type" translation was last checked.
 */
class LocaleTranslationSource {

  /**
   * List of locale file object.
   *
   * Valid keys are LOCALE_TRANSLATION_LOCAL or LOCALE_TRANSLATION_REMOTE.
   *
   * @var \Drupal\locale\File\LocaleFile[]
   */
  public array $files = [];

  /**
   * The server pattern.
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public string $server_pattern = '';

  /**
   * The project name.
   *
   * @var string
   */
  public string $name;

  /**
   * The version.
   *
   * @var string
   */
  public string $version = '';

  /**
   * The core for the Server pattern, always "all".
   *
   * @var string
   */
  public string $core = 'all';

  public function __construct(
    public string $project,
    public string $langcode,
    public string $type = '',
    public int $timestamp = 0,
    public string $hash = '',
    public int $last_checked = 0,
  ) {
    $this->name = $project;
  }

  /**
   * Create a LocaleTranslationSource object from a project.
   *
   * @param \Drupal\locale\LocaleTranslatableProject $project
   *   The locale project.
   * @param string $langcode
   *   The language code to create this source for.
   *
   * @return static
   */
  public static function fromProject(LocaleTranslatableProject $project, string $langcode): static {
    $source = new static($project->name, $langcode);
    $source->server_pattern = $project->server_pattern;
    $source->version = $project->version;
    return $source;
  }

}
