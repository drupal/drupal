<?php

declare(strict_types=1);

namespace Drupal\migrate_source_annotation_bc_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 * A migration source plugin with annotations and a single provider.
 *
 * This plugin exists to test backwards compatibility of source plugin discovery
 * for plugin classes using annotations. This class has no providers other than
 * 'migrate_source_annotation_bc_test' and 'core'. This class and its annotation
 * should remain until annotation support is completely removed.
 *
 * @MigrateSource(
 *   id = "annotated",
 *   source_module = "migrate"
 * )
 */
class MigrateSourceWithAnnotations extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Annotated';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new \ArrayIterator();
  }

}
