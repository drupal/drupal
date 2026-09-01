<?php

namespace Drupal\locale;

use Drupal\locale\File\LocaleFile;
use Drupal\locale\Model\SourceType;

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
 *    - "type": One of \Drupal\locale\Model\SourceType's values.
 *    - "timestamp": The creation time of the "type" translation (file).
 *    - "last_checked": The time when the "type" translation was last checked.
 */
class LocaleTranslationSource {

  /**
   * List of locale file object.
   *
   * Valid keys are SourceType::Local->value or
   * SourceType::Remote->value.
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

  /**
   * Returns type of the source.
   *
   * @return \Drupal\locale\Model\SourceType|null
   *   The source type.
   */
  public function getType(): SourceType|null {
    return SourceType::tryFrom($this->type);
  }

  /**
   * Returns file for the source.
   *
   * @return \Drupal\locale\File\LocaleFile|null
   *   The file for the given source.
   */
  public function getFile(SourceType $translationSource): LocaleFile|null {
    return $this->files[$translationSource->value] ?? NULL;
  }

  /**
   * Returns whether an update is available.
   *
   * @return bool
   *   There is an update available or not.
   */
  public function isUpdateAvailable(): bool {
    return $this->getType() == SourceType::Local || $this->getType() == SourceType::Remote;
  }

}
