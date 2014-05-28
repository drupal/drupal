<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Callback.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * This plugin allows source value to be passed to a callback.
 *
 * The current value is passed to a callable that returns the processed value.
 * This plugin allows simple processing of the value, such as strtolower(). The
 * callable takes the value as the single mandatory argument. No additional
 * arguments can be passed to the callback as this would make the migration YAML
 * file too complex.
 *
 * @MigrateProcessPlugin(
 *   id = "callback"
 * )
 */
class Callback extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if (is_callable($this->configuration['callable'])) {
      $value = call_user_func($this->configuration['callable'], $value);
    }
    return $value;
  }

}
