<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

use Drupal\Component\Serialization\Yaml;

/**
 * Caches YAML parsing in a cache collector.
 */
class YamlCacheCollector extends FileParsingCacheCollectorBase {

  /**
   * {@inheritdoc}
   */
  protected function parseFile($file): array {
    return Yaml::decode($file) ?? [];
  }

}
