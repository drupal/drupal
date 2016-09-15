<?php

namespace Drupal\migrate\Plugin;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Row;

/**
 * Defines an interface for migrate sources.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Annotation\MigrateSource
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see plugin_api
 *
 * @ingroup migration
 */
interface MigrateSourceInterface extends \Countable, \Iterator, PluginInspectionInterface {

  /**
   * Returns available fields on the source.
   *
   * @return array
   *   Available fields in the source, keys are the field machine names as used
   *   in field mappings, values are descriptions.
   */
  public function fields();

  /**
   * Adds additional data to the row.
   *
   * @param \Drupal\Migrate\Row $row
   *   The row object.
   *
   * @return bool
   *   FALSE if this row needs to be skipped.
   */
  public function prepareRow(Row $row);

  /**
   * Allows class to decide how it will react when it is treated like a string.
   */
  public function __toString();

  /**
   * Defines the source fields uniquely identifying a source row.
   *
   * None of these fields should contain a NULL value. If necessary, use
   * prepareRow() or hook_migrate_prepare_row() to rewrite NULL values to
   * appropriate empty values (such as '' or 0).
   *
   * @return array
   *   Array keyed by source field name, with values being a schema array
   *   describing the field (such as ['type' => 'string]).
   */
  public function getIds();

}
