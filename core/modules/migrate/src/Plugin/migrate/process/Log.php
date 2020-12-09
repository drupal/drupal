<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Logs values without changing them.
 *
 * The log plugin will log the values that are being processed by other plugins.
 *
 * Example:
 * @code
 * process:
 *   bar:
 *     plugin: log
 *     source: foo
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "log"
 * )
 */
class Log extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $is_object = is_object($value);
    if (is_null($value) || is_bool($value)) {
      $export = var_export($value, TRUE);
    }
    elseif (is_float($value)) {
      $export = sprintf('%f', $value);
    }
    elseif ($is_object && method_exists($value, 'toString')) {
      $export = print_r($value->toString(), TRUE);
    }
    elseif ($is_object && method_exists($value, 'toArray')) {
      $export = print_r($value->toArray(), TRUE);
    }
    elseif (is_string($value) || is_numeric($value) || is_array($value)) {
      $export = print_r($value, TRUE);
    }
    elseif ($is_object && method_exists($value, '__toString')) {
      $export = print_r((string) $value, TRUE);
    }
    else {
      $export = NULL;
    }

    $class_name = $export !== NULL && $is_object
      ? $class_name = get_class($value) . ":\n"
      : '';

    $message = $export === NULL
      ? "Unable to log the value for '$destination_property'"
      : "'$destination_property' value is $class_name'$export'";

    // Log the value.
    $migrate_executable->saveMessage($message);
    // Pass through the same value we received.
    return $value;
  }

}
