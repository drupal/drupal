<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\MultipleColumnsMap.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * This plugin changes the current value based on a static lookup map.
 *
 * @see https://drupal.org/node/2143521
 *
 * @MigrateProcessPlugin(
 *   id = "static_map"
 * )
 */
class StaticMap extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
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
      if (empty($this->configuration['bypass'])) {
        throw new MigrateException('Lookup failed.');
      }
      else {
        return $value;
      }
    }
    return $new_value;
  }

}

