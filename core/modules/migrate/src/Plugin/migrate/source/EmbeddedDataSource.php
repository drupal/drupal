<?php

namespace Drupal\migrate\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Allows source data to be defined in the configuration of the source plugin.
 *
 * The embedded_data source plugin is used to inject source data from the plugin
 * configuration. One use case is when some small amount of fixed data is
 * imported, so that it can be referenced by other migrations. Another use case
 * is testing.
 *
 * Available configuration keys:
 * - data_rows: The source data array. Each source row should be an associative
 *   array of values keyed by field names.
 * - ids: An associative array of fields uniquely identifying a source row.
 *   See \Drupal\migrate\Plugin\MigrateSourceInterface::getIds() for more
 *   information.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: embedded_data
 *   data_rows:
 *     -
 *       channel_machine_name: music
 *       channel_description: Music
 *     -
 *       channel_machine_name: movies
 *       channel_description: Movies
 *   ids:
 *     channel_machine_name:
 *       type: string
 * @endcode
 *
 * This example migrates a channel vocabulary specified in the source section.
 *
 * For additional configuration keys, refer to the parent class:
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 */
#[MigrateSource('embedded_data')]
class EmbeddedDataSource extends SourcePluginBase {

  /**
   * Data obtained from the source plugin configuration.
   *
   * @var array[]
   *   Array of data rows, each one an array of values keyed by field names.
   */
  protected $dataRows = [];

  /**
   * Description of the unique ID fields for this source.
   *
   * @var array[]
   *   Each array member is keyed by a field name, with a value that is an
   *   array with a single member with key 'type' and value a column type such
   *   as 'integer'.
   */
  protected $ids = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->dataRows = $configuration['data_rows'];
    $this->ids = $configuration['ids'];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    if ($this->count() > 0) {
      $first_row = reset($this->dataRows);
      $field_names = array_keys($first_row);
      return array_combine($field_names, $field_names);
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new \ArrayIterator($this->dataRows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'Embedded data';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->ids;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE): int {
    // We do not want this source plugin to have a cacheable count.
    // @see \Drupal\migrate_cache_counts_test\Plugin\migrate\source\CacheableEmbeddedDataSource
    return count($this->dataRows);
  }

}
