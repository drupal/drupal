<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin extracts a value from an array.
 *
 * @link https://www.drupal.org/node/2152731 Online handbook documentation for extract process plugin @endlink
 *
 * @MigrateProcessPlugin(
 *   id = "extract",
 *   handle_multiples = TRUE
 * )
 */
class Extract extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException('Input should be an array.');
    }
    $new_value = NestedArray::getValue($value, $this->configuration['index'], $key_exists);
    if (!$key_exists) {
      if (isset($this->configuration['default'])) {
        $new_value = $this->configuration['default'];
      }
      else {
        throw new MigrateException('Array index missing, extraction failed.');
      }
    }
    return $new_value;
  }

}
