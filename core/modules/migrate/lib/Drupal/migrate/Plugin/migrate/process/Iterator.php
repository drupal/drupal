<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Iterator.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * This plugin iterates and processes an array.
 *
 * @see https://drupal.org/node/2135345
 *
 * @MigrateProcessPlugin(
 *   id = "iterator",
 *   handle_multiples = TRUE
 * )
 */
class Iterator extends ProcessPluginBase {

  /**
   * Runs a process pipeline on each destination property per list item.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $return = array();
    foreach ($value as $key => $new_value) {
      $new_row = new Row($new_value, array());
      $migrate_executable->processRow($new_row, $this->configuration['process']);
      $destination = $new_row->getDestination();
      if (array_key_exists('key', $this->configuration)) {
        $key = $this->transformKey($key, $migrate_executable, $new_row);
      }
      $return[$key] = $destination;
    }
    return $return;
  }

  /**
   * Runs the process pipeline for the current key.
   *
   * @param string|int $key
   *   The current key.
   * @param \Drupal\migrate\MigrateExecutable $migrate_executable
   *   The migrate executable helper class.
   * @param \Drupal\migrate\Row $row
   *   The current row after processing.
   * @return mixed
   *   The transformed key.
   */
  protected function transformKey($key, MigrateExecutable $migrate_executable, Row $row) {
    $process = array('key' => $this->configuration['key']);
    $migrate_executable->processRow($row, $process, $key);
    return $row->getDestinationProperty('key');
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }
}
