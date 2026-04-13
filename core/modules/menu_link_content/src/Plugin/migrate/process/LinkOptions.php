<?php

namespace Drupal\menu_link_content\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts links options.
 *
 * @deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use
 * \Drupal\migrate\Plugin\migrate\process\LinkOptions instead.
 *
 * @see https://www.drupal.org/node/3572239
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
class LinkOptions extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.4.0 and is removed from drupal:13.0.0. Use \Drupal\migrate\Plugin\migrate\process\LinkOptions instead. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
