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
    // Log the value.
    $migrate_executable->saveMessage($value);

    // Pass through the same value we received.
    return $value;
  }

}
