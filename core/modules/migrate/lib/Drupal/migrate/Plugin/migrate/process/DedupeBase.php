<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\process\DedupeBase.
 */

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * This abstract base contains the dedupe logic.
 *
 * These plugins avoid duplication at the destination. For example, when
 * creating filter format names, the current value is checked against the
 * existing filter format names and if it exists, a numeric postfix is added
 * and incremented until a unique value is created.
 */
abstract class DedupeBase extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    $i = 1;
    $postfix = isset($this->configuration['postfix']) ? $this->configuration['postfix'] : '';
    $new_value = $value;
    while ($this->exists($new_value)) {
      $new_value = $value . $postfix . $i++;
    }
    return $new_value;
  }

  /**
   * This is a query checking the existence of some value.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value exists.
   */
  abstract protected function exists($value);

}
