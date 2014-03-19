<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\FlagSkippingRow.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * If the source evaluates to empty, we skip the current row.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_row_on_empty"
 * )
 */
class SkipRowOnEmpty extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      throw new MigrateSkipRowException();
    }
    return $value;
  }

}
