<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Flatten.
 */

namespace Drupal\migrate\Plugin\migrate\process;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin flattens the current value.
 *
 * During some types of processing (e.g. user permission splitting), what was
 * once a single value gets transformed into multiple values. This plugin will
 * flatten them back down to single values again.
 *
 * @see https://www.drupal.org/node/2154215
 *
 * @MigrateProcessPlugin(
 *   id = "flatten",
 *   handle_multiples = TRUE
 * )
 */
class Flatten extends ProcessPluginBase {

  /**
   * Flatten nested array values to single array values.
   *
   * For example, array(array(1, 2, array(3, 4))) becomes array(1, 2, 3, 4).
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value)), FALSE);
  }

}
