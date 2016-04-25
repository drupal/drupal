<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\Component\Utility\Unicode;

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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $i = 1;
    $postfix = isset($this->configuration['postfix']) ? $this->configuration['postfix'] : '';
    $start = isset($this->configuration['start']) ? $this->configuration['start'] : 0;
    if (!is_int($start)) {
      throw new MigrateException('The start position configuration key should be an integer. Omit this key to capture from the beginning of the string.');
    }
    $length = isset($this->configuration['length']) ? $this->configuration['length'] : NULL;
    if (!is_null($length) && !is_int($length)) {
      throw new MigrateException('The character length configuration key should be an integer. Omit this key to capture the entire string.');
    }
    // Use optional start or length to return a portion of deduplicated value.
    $value = Unicode::substr($value, $start, $length);
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
