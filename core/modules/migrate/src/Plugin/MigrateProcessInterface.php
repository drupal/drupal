<?php

namespace Drupal\migrate\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * An interface for migrate process plugins.
 *
 * A process plugin will typically implement the transform() method to perform
 * its work. However, it is possible instead for a process plugin to use any
 * number of methods, thus offering different alternatives ways of processing.
 * In this case, the transform() method should not be implemented, and the
 * plugin configuration must provide the name of the method to be called via the
 * "method" key. Each method must have the same signature as transform().
 * The base class \Drupal\migrate\ProcessPluginBase takes care of implementing
 * transform() and calling the configured method. See
 * \Drupal\migrate\Plugin\migrate\process\SkipOnEmpty and
 * d6_field_instance_widget_settings.yml for examples.
 *
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\ProcessPluginBase
 * @see \Drupal\migrate\Annotation\MigrateProcessPlugin
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
   * @return string|array
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

}
