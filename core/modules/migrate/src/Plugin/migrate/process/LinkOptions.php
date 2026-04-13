<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts links options.
 *
 * Examples:
 *
 * @code
 * process:
 *   link/options:
 *     plugin: link_options
 *     source: options
 * @endcode
 *
 * This will convert the query options of the link.
 */
#[MigrateProcess(
  id: "link_options",
  handle_multiples: TRUE,
)]
class LinkOptions extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (isset($value['query']) && is_string($value['query'])) {
      // If the query parameters are stored as a string, such as 'a=1&b=2', then
      // convert it into an array.
      parse_str($value['query'], $value['query']);
    }
    return $value;
  }

}
