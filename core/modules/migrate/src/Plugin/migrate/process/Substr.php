<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\Component\Utility\Unicode;

/**
 * This plugin returns a substring of the current value.
 *
 * @MigrateProcessPlugin(
 *   id = "substr"
 * )
 */
class Substr extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $start = isset($this->configuration['start']) ? $this->configuration['start'] : 0;
    if (!is_int($start)) {
      throw new MigrateException('The start position configuration value should be an integer. Omit this key to capture from the beginning of the string.');
    }
    $length = isset($this->configuration['length']) ? $this->configuration['length'] : NULL;
    if (!is_null($length) && !is_int($length)) {
      throw new MigrateException('The character length configuration value should be an integer. Omit this key to capture from the start position to the end of the string.');
    }
    if (!is_string($value)) {
      throw new MigrateException('The input value must be a string.');
    }

    // Use optional start or length to return a portion of $value.
    $new_value = Unicode::substr($value, $start, $length);
    return $new_value;
  }

}
