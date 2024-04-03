<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * An interface for migrate process plugins.
 *
 * Migrate process plugins transform the input value.For example, transform a
 * human provided name into a machine name, look up an identifier in a previous
 * migration and so on.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\ProcessPluginBase
 * @see \Drupal\migrate\Attribute\MigrateProcess
 * @see plugin_api
 *
 * @ingroup migration
 */
interface MigrateProcessInterface extends PluginInspectionInterface {

  /**
   * Performs the associated process.
   *
   * @param mixed $value
   *   The value to be transformed.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process. Normally, just transforming the value
   *   is adequate but very rarely you might need to change two columns at the
   *   same time or something like that.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return mixed
   *   The newly transformed value.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property);

  /**
   * Indicates whether the returned value requires multiple handling.
   *
   * @return bool
   *   TRUE when the returned value contains a list of values to be processed.
   *   For example, when the 'source' property is a string and the value found
   *   is an array.
   */
  public function multiple();

  /**
   * Determines if the pipeline should stop processing.
   *
   * @return bool
   *   A boolean value indicating if the pipeline processing should stop.
   */
  public function isPipelineStopped(): bool;

  /**
   * Resets the internal data of a plugin.
   */
  public function reset(): void;

}
