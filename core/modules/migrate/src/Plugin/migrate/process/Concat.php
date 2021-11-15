<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Concatenates a set of strings.
 *
 * The concat plugin is used to concatenate strings. For example, imploding a
 * set of strings into a single string.
 *
 * Available configuration keys:
 * - delimiter: (optional) A delimiter, or glue string, to insert between the
 *   strings.
 *
 * Examples:
 *
 * @code
 * process:
 *   new_text_field:
 *     plugin: concat
 *     source:
 *       - foo
 *       - bar
 * @endcode
 *
 * This will set new_text_field to the concatenation of the 'foo' and 'bar'
 * source values. For example, if the 'foo' property is "Rosa" and the 'bar'
 * property is "Parks", new_text_field will be "RosaParks".
 *
 * You can also specify a delimiter.
 *
 * @code
 * process:
 *   new_text_field:
 *     plugin: concat
 *     source:
 *       - foo
 *       - bar
 *     delimiter: /
 * @endcode
 *
 * This will set new_text_field to the concatenation of the 'foo' source value,
 * the delimiter and the 'bar' source value. For example, using the values above
 * and "/" as the delimiter, if the 'foo' property is "Rosa" and the 'bar'
 * property is "Rosa", new_text_field will be "Rosa/Parks".
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "concat",
 *   handle_multiples = TRUE
 * )
 */
class Concat extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $delimiter = $this->configuration['delimiter'] ?? '';
      return implode($delimiter, $value);
    }
    else {
      throw new MigrateException(sprintf('%s is not an array', var_export($value, TRUE)));
    }
  }

}
