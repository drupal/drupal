<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Skips processing the current row when a source value is not set.
 *
 * The skip_row_if_not_set process plugin checks whether a value is set. If the
 * value is set, it is returned. Otherwise, a MigrateSkipRowException
 * is thrown.
 *
 * Available configuration keys:
 * - index: The source property to check for.
 *
 * Example:
 *
 * @code
 *  process:
 *    settings:
 *      # Check if the "contact" key exists in the "data" array.
 *      plugin: skip_row_if_not_set
 *      index: contact
 *      source: data
 * @endcode
 *
 * This will return $data['contact'] if it exists. Otherwise, the row will be
 * skipped.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "skip_row_if_not_set",
 *   handle_multiples = TRUE
 * )
 */
class SkipRowIfNotSet extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($value[$this->configuration['index']])) {
      throw new MigrateSkipRowException();
    }
    return $value[$this->configuration['index']];
  }

}
