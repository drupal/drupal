<?php

declare(strict_types=1);

namespace Drupal\locale;

/**
 * Translatable project for interface translation.
 */
class LocaleTranslatableProject {

  /**
   * Locale translatable project value object.
   *
   * @param string $name
   *   The project name.
   * @param string $type
   *   The extension type.
   * @param string $core
   *   The core version constraint.
   * @param string $version
   *   The project's version.
   * @param string $server_pattern
   *   The server pattern.
   * @param array $info
   *   The .info.yml parsed project information.
   * @param string|null $langcode
   *   The language code.
   * @param int $weight
   *   Allows to order the projects.
   * @param bool $status
   *   Whether the project is enabled.
   */
  public function __construct(
    public string $name,
    public string $type,
    public string $core,
    public string $version,
    public string $server_pattern,
    public array $info = [],
    public ?string $langcode = NULL,
    protected int $weight = 0,
    protected bool $status = FALSE,
  ) {
    $emptyParameters = [];
    foreach (['name', 'type', 'core', 'server_pattern'] as $property) {
      if (empty(trim($$property))) {
        $emptyParameters[] = '$' . $property;
      }
    }
    if ($emptyParameters) {
      throw new \InvalidArgumentException('The following parameters are empty: ' . implode(', ', $emptyParameters));
    }
  }

  /**
   * Sets the project status.
   *
   * @param bool $status
   *   A flag indicating whether the project is enabled.
   *
   * @return $this
   */
  public function setStatus(bool $status): static {
    $this->status = $status;
    return $this;
  }

  /**
   * Returns the project status.
   *
   * @return bool
   *   Returns TRUE if the project is enabled, FALSE otherwise.
   */
  public function getStatus(): bool {
    return $this->status;
  }

  /**
   * Sets the language code.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return $this
   */
  public function setLangcode(string $langcode): static {
    $this->langcode = $langcode;
    return $this;
  }

  /**
   * Returns the project langcode.
   *
   * @return string|null
   *   Returns the language code or NULL if it was not set.
   */
  public function getLangcode(): ?string {
    return $this->langcode;
  }

  /**
   * Sets the weight.
   *
   * @param int $weight
   *   The weight.
   *
   * @return $this
   */
  public function setWeight(int $weight): static {
    $this->weight = $weight;
    return $this;
  }

  /**
   * Returns the weight.
   *
   * @return int
   *   Returns the weight.
   */
  public function getWeight(): int {
    return $this->weight;
  }

  /**
   * Creates a new instance from an array.
   *
   * @param array{name: string, type: string, core: string, version: string, server_pattern?: string|null, info?: array, langcode: string, status?: bool, weight?: int} $data
   *   The array to create the instance from.
   *
   * @return static
   */
  public static function createFromArray(array $data): static {
    $status = $data['status'] ?? $data['project_status'] ?? FALSE;
    return new static(
      $data['name'],
      $data['type'] ?? $data['project_type'],
      $data['core'],
      $data['version'],
      $data['server_pattern'] ?? \Drupal::TRANSLATION_DEFAULT_SERVER_PATTERN,
      $data['info'] ?? [],
      $data['langcode'] ?? NULL,
      $data['weight'] ?? 0,
      (bool) $status,
    );
  }

  /**
   * Returns an array representation on the object.
   *
   * @return array{name: string, type: string, core: string, version: string, server_pattern: string, info?: array, langcode: string, status?: bool, weight?: int}
   *   The array representation on the object.
   */
  public function toArray(): array {
    return [
      'name' => $this->name,
      'type' => $this->type,
      'core' => $this->core,
      'version' => $this->version,
      'server_pattern' => $this->server_pattern,
      'langcode' => $this->langcode,
      'weight' => $this->weight,
      'status' => $this->status,
    ];
  }

}
