<?php

declare(strict_types=1);

namespace Drupal\migrate_source_annotation_bc_test\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\EmptySource;

/**
 * A migration source plugin with annotations and multiple providers.
 *
 * This plugin exists to test backwards compatibility of source plugin discovery
 * for plugin classes using annotations. This class has an additional provider,
 * because it extends a plugin in migrate_drupal. This class and its annotation
 * should remain until annotation support is completely removed.
 *
 * @MigrateSource(
 *   id = "annotated_multiple_providers",
 *   source_module = "migrate"
 * )
 */
class MigrateSourceWithAnnotationsMultipleProviders extends EmptySource {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Annotated multiple providers';
  }

}
