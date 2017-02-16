<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\Component\Utility\Unicode;

/**
 * Returns a substring of the input value.
 *
 * The substr process plugin returns the portion of the input value specified by
 * the start and length parameters. This is a wrapper around the PHP substr()
 * function.
 *
 * Available configuration keys:
 * - start: (optional) The returned string will start this many characters after
 *   the beginning of the string. Defaults to NULL.
 * - length: (optional) The maximum number of characters in the returned
 *   string. Defaults to NULL.
 *
 * If start is NULL and length is an integer, the start position is the
 * beginning of the string. If start is an integer and length is NULL, the
 * substring starting from the start position until the end of the string will
 * be returned. If both start and length are NULL the entire string is returned.
 *
 * Example:
 *
 * @code
 * process:
 *   new_text_field:
 *     plugin: substr
 *     source: some_text_field
 *       start: 6
 *       length: 10
 * @endcode
 *
 * If some_text_field was 'Marie Skłodowska Curie' then
 * $destination['new_text_field'] would be 'Skłodowska'.
 *
 * The PHP equivalent of this is:
 *
 * @code
 * $destination['new_text_field'] = substr($source['some_text_field'], 6, 10);
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "substr"
 * )
 */
class Substr extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $start = isset($this->configuration['start']) ? $this->configuration['start'] : 0;
    if (!is_int($start)) {
      throw new MigrateException('The start position configuration value should be an integer. Omit this key to capture from the beginning of the string.');
    }
    $length = isset($this->configuration['length']) ? $this->configuration['length'] : NULL;
    if (!is_null($length) && !is_int($length)) {
      throw new MigrateException('The character length configuration value should be an integer. Omit this key to capture from the start position to the end of the string.');
    }
    if (!is_string($value)) {
      throw new MigrateException('The input value must be a string.');
    }

    // Use optional start or length to return a portion of $value.
    $new_value = Unicode::substr($value, $start, $length);
    return $new_value;
  }

}
