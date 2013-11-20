<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\DefaultValue.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;


/**
 * This plugin sets missing values on the destination.
 *
 * @PluginId("default_value")
 */
class DefaultValue extends PluginBase implements MigrateProcessInterface {

 /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    return isset($value) ? $value : $this->configuration['default_value'];
  }
}
