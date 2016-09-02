<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin sets missing values on the destination.
 *
 * @link https://www.drupal.org/node/2135313 Online handbook documentation for default_value process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "default_value"
 * )
 */
class DefaultValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!empty($this->configuration['strict'])) {
      return isset($value) ? $value : $this->configuration['default_value'];
    }
    return $value ?: $this->configuration['default_value'];
  }

}
