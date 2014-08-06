<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateSourceInterface.
 */

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
interface MigrateSourceInterface extends \Countable, PluginInspectionInterface {

  /**
   * Returns available fields on the source.
   *
   * @return array
   *   Available fields in the source, keys are the field machine names as used
   *   in field mappings, values are descriptions.
   */
  public function fields();

  /**
   * Returns the iterator that will yield the row arrays to be processed.
   *
   * @return \Iterator
   *   The iterator object.
   *
   * @throws \Exception
   *   Cannot obtain a valid iterator.
   */
  public function getIterator();

  /**
   * Add additional data to the row.
   *
   * @param \Drupal\Migrate\Row $row
   *   The row object.
   *
   * @return bool
   *   FALSE if this row needs to be skipped.
   */
  public function prepareRow(Row $row);

  public function __toString();

  /**
   * Get the source ids.
   *
   * @return array
   *   The source ids.
   */
  public function getIds();

}
