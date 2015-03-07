<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\d6\SkipProcessOnEmpty.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * If the value evaluates to false, skip further processing.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_process_on_empty"
 * )
 */
class SkipProcessOnEmpty extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Skip the rest of the processing on 0.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      throw new MigrateSkipProcessException();
    }
    return $value;
  }

}
