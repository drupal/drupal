<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\Concat.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\String;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Concatenates the strings in the current value.
 *
 * @MigrateProcessPlugin(
 *   id = "concat",
 *   handle_multiples = TRUE
 * )
 */
class Concat extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Concatenates the strings in the current value.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_array($value)) {
      $delimiter = isset($this->configuration['delimiter']) ? $this->configuration['delimiter'] : '';
      return implode($delimiter, $value);
    }
    else {
      throw new MigrateException(sprintf('%s is not an array', String::checkPlain(var_export($value, TRUE))));
    }
  }

}
