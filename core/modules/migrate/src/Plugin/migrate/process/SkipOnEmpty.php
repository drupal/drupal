<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * Skips processing the current row when the input value is empty.
 *
 * The skip_on_empty process plugin checks to see if the current input value
 * is empty (empty string, NULL, FALSE, 0, '0', or an empty array). If so, the
 * further processing of the property or the entire row (depending on the chosen
 * method) is skipped and will not be migrated.
 *
 * Available configuration keys:
 * - method: (optional) What to do if the input value is empty. Possible values:
 *   - row: Skips the entire row when an empty value is encountered.
 *   - process: Prevents further processing of the input property when the value
 *     is empty and sets the value to NULL.
 * - message: (optional) A message to be logged in the {migrate_message_*} table
 *   for this row. Messages are only logged for the 'row' method. If not set,
 *   nothing is logged in the message table.
 *
 * Examples:
 *
 * @code
 * process:
 *   field_type_exists:
 *     plugin: skip_on_empty
 *     method: row
 *     source: field_name
 *     message: 'Field field_name is missing'
 * @endcode
 * If 'field_name' is empty, the entire row is skipped and the message 'Field
 * field_name is missing' is logged in the message table.
 *
 * @code
 * process:
 *   parent:
 *     -
 *       plugin: skip_on_empty
 *       method: process
 *       source: parent
 *     -
 *       plugin: migration_lookup
 *       migration: d6_taxonomy_term
 * @endcode
 * If 'parent' is empty, any further processing of the property is skipped and
 * the next process plugin (migration_lookup) will not be run. If the
 * migration_lookup process is executed it will use 'parent' as the source.
 * Combining skip_on_empty and migration_lookup is a typical process pipeline
 * combination for hierarchical entities where the root entity does not have a
 * parent.
 *
 * @code
 * process:
 *   parent:
 *     -
 *       plugin: skip_on_empty
 *       method: process
 *       source: parent
 *     -
 *       plugin: migration_lookup
 *       migration: d6_taxonomy_term
 *       source: original_term
 * @endcode
 * If 'parent' is empty, any further processing of the property is skipped and
 * the next process plugin (migration_lookup) will not be run. If the
 * migration_lookup process is executed it will use 'original_term' as the
 * source.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('skip_on_empty')]
class SkipOnEmpty extends ProcessPluginBase {

  /**
   * Skips the current row when value is not set.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if the source property is not set and the row should be skipped,
   *   records with STATUS_IGNORED status in the map.
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      $message = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      throw new MigrateSkipRowException($message);
    }
    return $value;
  }

  /**
   * Stops processing the current property when value is not set.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The input value, $value, if it is not empty.
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      $this->stopPipeline();
      return NULL;
    }
    return $value;
  }

}
