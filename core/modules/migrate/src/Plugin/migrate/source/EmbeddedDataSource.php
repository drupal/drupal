<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\source\EmbeddedDataSource.
 */

namespace Drupal\migrate\Plugin\migrate\source;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Source which takes its data directly from the plugin config.
 *
 * @MigrateSource(
 *   id = "embedded_data"
 * )
 */
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
  public function count() {
    return count($this->dataRows);
  }

}
