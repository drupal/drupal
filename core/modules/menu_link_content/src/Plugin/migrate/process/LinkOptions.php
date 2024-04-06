<?php

namespace Drupal\menu_link_content\Plugin\migrate\process;

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
    if (isset($value['query'])) {
      // If the query parameters are stored as a string (as in D6), convert it
      // into an array.
      if (is_string($value['query'])) {
        parse_str($value['query'], $old_query);
      }
      else {
        $old_query = $value['query'];
      }
      $value['query'] = $old_query;
    }
    return $value;
  }

}
