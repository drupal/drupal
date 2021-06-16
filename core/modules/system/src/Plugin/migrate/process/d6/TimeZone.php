<?php

namespace Drupal\system\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the D6 Timezone offset into a D8 compatible timezone name.
 *
 * @MigrateProcessPlugin(
 *   id = "timezone"
 * )
 */
class TimeZone extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $offset = $value;
    // Convert the integer value of the offset (which can be either
    // negative or positive) to a timezone name.
    // Note: Daylight saving time is not to be used.
    $timezone_name = timezone_name_from_abbr('', intval($offset), 0);
    if (!$timezone_name) {
      $timezone_name = 'UTC';
    }

    return $timezone_name;
  }

}
