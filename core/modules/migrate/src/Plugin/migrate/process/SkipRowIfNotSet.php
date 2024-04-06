<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
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
 * - message: (optional) A message to be logged in the {migrate_message_*} table
 *   for this row. If not set, nothing is logged in the message table.
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
 *      message: "Missed the 'data' key"
 * @endcode
 *
 * This will return $data['contact'] if it exists. Otherwise, the row will be
 * skipped and the message "Missed the 'data' key" will be logged in the
 * message table.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "skip_row_if_not_set",
  handle_multiples: TRUE,
)]
class SkipRowIfNotSet extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($value[$this->configuration['index']])) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value[$this->configuration['index']];
  }

}
