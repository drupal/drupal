<?php

declare(strict_types=1);

namespace Drupal\Core\Theme\Icon;

/**
 * Interface for icon_extractor plugins.
 *
 * @internal
 *   This API is experimental.
 */
interface IconExtractorWithFinderInterface extends IconExtractorInterface {

  /**
   * Create files data from sources config.
   *
   * @return array<string, array<string, string|null>>
   *   List of files with metadata.
   */
  public function getFilesFromSources(): array;

}
