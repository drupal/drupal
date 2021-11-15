<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;

/**
 * This plugin ensures the source value is unique.
 *
 * The MakeUniqueBase process plugin is used to avoid duplication at the
 * destination. For example, when creating filter format names, the source
 * value is checked against the existing filter format names and if it exists,
 * a numeric postfix is added and incremented until a unique value is created.
 * An optional postfix string can be insert before the numeric postfix.
 *
 * Available configuration keys
 *   - start: (optional) The position at which to start reading.
 *   - length: (optional) The number of characters to read.
 *   - postfix: (optional) A string to insert before the numeric postfix.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
abstract class MakeUniqueBase extends ProcessPluginBase {

  /**
   * Creates a unique value based on the source value.
   *
   * @param string $value
   *   The input string.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return string
   *   The unique version of the input value.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $i = 1;
    $postfix = $this->configuration['postfix'] ?? '';
    $start = $this->configuration['start'] ?? 0;
    if (!is_int($start)) {
      throw new MigrateException('The start position configuration key should be an integer. Omit this key to capture from the beginning of the string.');
    }
    $length = $this->configuration['length'] ?? NULL;
    if (!is_null($length) && !is_int($length)) {
      throw new MigrateException('The character length configuration key should be an integer. Omit this key to capture the entire string.');
    }
    // Use optional start or length to return a portion of the unique value.
    $value = mb_substr($value, $start, $length);
    $new_value = $value;
    while ($this->exists($new_value)) {
      $new_value = $value . $postfix . $i++;
    }
    return $new_value;
  }

  /**
   * This is a query checking the existence of some value.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value exists.
   */
  abstract protected function exists($value);

}
