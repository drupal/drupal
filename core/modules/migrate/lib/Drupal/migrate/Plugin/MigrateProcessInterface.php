<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\MigrateProcessInterface.
 */

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * An interface for migrate processes.
 */
interface MigrateProcessInterface extends PluginInspectionInterface {

  /**
   * Performs the associated process.
   *
   * @param $value
   *   The value to be transformed.
   * @param \Drupal\migrate\MigrateExecutable $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process. Normally, just transforming the
   *   value is adequate but very rarely you might need to change two columns
   *   at the same time or something like that.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used
   *   together with the $row above.
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property);

  /**
   * Indicates whether the returned value requires multiple handling.
   *
   * @return bool
   *   TRUE when the returned value contains a list of values to be processed.
   *   For example, when the 'source' property is a string and the value found
   *   is an array.
   */
  public function multiple();
}
