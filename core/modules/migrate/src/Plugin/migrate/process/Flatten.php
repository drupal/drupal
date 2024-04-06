<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Flattens the source value.
 *
 * The flatten process plugin converts a nested array into a flat array. For
 * example [[1, 2, [3, 4]], [5], 6] becomes [1, 2, 3, 4, 5, 6]. During some
 * types of processing (e.g. user permission splitting), what was once a
 * one-dimensional array gets transformed into a multidimensional array. This
 * plugin will flatten them back down to one-dimensional arrays again.
 *
 * Example:
 *
 * @code
 * process:
 *   tags:
 *      -
 *        plugin: default_value
 *        source: foo
 *        default_value: [bar, [alpha, beta]]
 *      -
 *        plugin: flatten
 * @endcode
 *
 * In this example, the default_value process returns [bar, [alpha, beta]]
 * (given a NULL value of foo). At this point, Migrate would try to import two
 * items: bar and [alpha, beta]. The latter is not a valid one and won't be
 * imported. We need to pass the values through the flatten processor to obtain
 * a three items array [bar, alpha, beta], suitable for import.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "flatten",
  handle_multiples: TRUE,
)]
class Flatten extends ProcessPluginBase {

  /**
   * Flatten nested array values to single array values.
   *
   * For example, [[1, 2, [3, 4]]] becomes [1, 2, 3, 4].
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value) && !is_object($value)) {
      $type = gettype($value);
      throw new MigrateException(sprintf("Input should be an array or an object, instead it was of type '%s'", $type));
    }
    return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value)), FALSE);
  }

}
