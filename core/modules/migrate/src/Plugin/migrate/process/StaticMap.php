<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\MultipleColumnsMap.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;

/**
 * This plugin changes the current value based on a static lookup map.
 *
 * @see https://www.drupal.org/node/2143521
 *
 * @MigrateProcessPlugin(
 *   id = "static_map"
 * )
 */
class StaticMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = $value;
    if (is_array($value)) {
      if (!$value) {
        throw new MigrateException('Can not lookup without a value.');
      }
    }
    else {
      $new_value = array($value);
    }
    $new_value = NestedArray::getValue($this->configuration['map'], $new_value, $key_exists);
    if (!$key_exists) {
      if (isset($this->configuration['default_value'])) {
        if (!empty($this->configuration['bypass'])) {
          throw new MigrateException('Setting both default_value and bypass is invalid.');
        }
        return $this->configuration['default_value'];
      }
      if (empty($this->configuration['bypass'])) {
        throw new MigrateSkipRowException();
      }
      else {
        return $value;
      }
    }
    return $new_value;
  }

}
