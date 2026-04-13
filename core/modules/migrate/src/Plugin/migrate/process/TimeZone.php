<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the Timezone offset into a Drupal compatible timezone name.
 */
#[MigrateProcess('timezone')]
class TimeZone extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $offset = $value;
    // Convert the integer value of the offset (which can be either
    // negative or positive) to a timezone name.
    // Note: Daylight saving time is not to be used.
    return timezone_name_from_abbr('', intval($offset), 0) ?: 'UTC';
  }

}
