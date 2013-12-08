<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\MultipleColumnsMap.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;

/**
 * This plugin changes the current value based on a static lookup map.
 *
 * @see https://drupal.org/node/2143521
 *
 * @PluginId("static_map")
 */
class StaticMap extends PluginBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      $value = array($value);
    }
    if (!$value) {
      throw new MigrateException('Can not lookup without a value.');
    }
    $new_value = NestedArray::getValue($this->configuration['map'], $value, $key_exists);
    if (!$key_exists) {
      throw new MigrateException('Lookup failed.');
    }
    return $new_value;
  }

}

