<?php

declare(strict_types=1);

namespace Drupal\migrate_cache_counts_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\EmbeddedDataSource;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * A copy of embedded_data which allows caching the count.
 */
#[MigrateSource('cacheable_embedded_data')]
class CacheableEmbeddedDataSource extends EmbeddedDataSource {

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE): int {
    return SourcePluginBase::count($refresh);
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return parent::count(TRUE);
  }

}
