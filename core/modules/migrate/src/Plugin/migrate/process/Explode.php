<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin explodes a delimited string into an array of values.
 *
 * @MigrateProcessPlugin(
 *   id = "explode"
 * )
 */
class Explode extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (is_string($value)) {
      if (!empty($this->configuration['delimiter'])) {
        $limit = isset($this->configuration['limit']) ? $this->configuration['limit'] : PHP_INT_MAX;
        return explode($this->configuration['delimiter'], $value, $limit);
      }
      else {
        throw new MigrateException('delimiter is empty');
      }
    }
    else {
      throw new MigrateException(sprintf('%s is not a string', var_export($value, TRUE)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
