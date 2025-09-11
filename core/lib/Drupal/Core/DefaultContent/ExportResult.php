<?php

declare(strict_types=1);

namespace Drupal\Core\DefaultContent;

use Drupal\Core\Serialization\Yaml;

/**
 * The result of exporting a content entity.
 *
 * @internal
 *   This API is experimental.
 */
final readonly class ExportResult {

  public function __construct(
    public array $data,
    public ExportMetadata $metadata,
  ) {}

  /**
   * Returns the exported entity data as YAML.
   *
   * @return string
   *   The exported entity data in YAML format.
   */
  public function __toString(): string {
    $data = [
      '_meta' => $this->metadata->get(),
    ] + $this->data;

    return Yaml::encode($data);
  }

}
