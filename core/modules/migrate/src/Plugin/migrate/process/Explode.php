<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Splits the source string into an array of strings, using a delimiter.
 *
 * This plugin creates an array of strings by splitting the source parameter on
 * boundaries formed by the delimiter.
 *
 * Available configuration keys:
 * - source: The source string.
 * - limit: (optional)
 *   - If limit is set and positive, the returned array will contain a maximum
 *     of limit elements with the last element containing the rest of string.
 *   - If limit is set and negative, all components except the last -limit are
 *     returned.
 *   - If the limit parameter is zero, then this is treated as 1.
 * - delimiter: The boundary string.
 * - strict: (optional) When this boolean is TRUE, the source should be strictly
 *   a string. If FALSE is passed, the source value is casted to a string before
 *   being split. Also, in this case, the values casting to empty strings are
 *   converted to empty arrays, instead of an array with a single empty string
 *   item ['']. Defaults to TRUE.
 *
 * Example:
 *
 * @code
 * process:
 *   bar:
 *     plugin: explode
 *     source: foo
 *     delimiter: /
 * @endcode
 *
 * If foo is "node/1", then bar will be ['node', '1']. The PHP equivalent of
 * this would be:
 *
 * @code
 *   $bar = explode('/', $foo);
 * @endcode
 *
 * @code
 * process:
 *   bar:
 *     plugin: explode
 *     source: foo
 *     limit: 1
 *     delimiter: /
 * @endcode
 *
 * If foo is "node/1/edit", then bar will be ['node', '1/edit']. The PHP
 * equivalent of this would be:
 *
 * @code
 *   $bar = explode('/', $foo, 1);
 * @endcode
 *
 * If the 'strict' configuration is set to FALSE, the input value is casted to a
 * string before being spilt:
 *
 * @code
 * process:
 *   bar:
 *     plugin: explode
 *     source: foo
 *     delimiter: /
 *     strict: false
 * @endcode
 *
 * If foo is 123 (as integer), then bar will be ['123']. If foo is TRUE, then
 * bar will be ['1']. The PHP equivalent of this would be:
 *
 * @code
 *   $bar = explode('/', (string) 123);
 *   $bar = explode('/', (string) TRUE);
 * @endcode
 *
 * If the 'strict' configuration is set to FALSE, the source value casting to
 * an empty string are converted to an empty array. For example, with the last
 * configuration, if foo is '', NULL or FALSE, then bar will be [].
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "explode"
 * )
 */
class Explode extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['delimiter'])) {
      throw new MigrateException('delimiter is empty');
    }

    $strict = array_key_exists('strict', $this->configuration) ? $this->configuration['strict'] : TRUE;
    if ($strict && !is_string($value)) {
      throw new MigrateException(sprintf('%s is not a string', var_export($value, TRUE)));
    }
    elseif (!$strict) {
      // Check if the incoming value can cast to a string.
      $original = $value;
      if (!is_string($original) && ($original != ($value = @strval($value)))) {
        throw new MigrateException(sprintf('%s cannot be casted to a string', var_export($original, TRUE)));
      }
      // Empty strings should be exploded to empty arrays.
      if ($value === '') {
        return [];
      }
    }

    $limit = $this->configuration['limit'] ?? PHP_INT_MAX;

    return explode($this->configuration['delimiter'], $value, $limit);
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
