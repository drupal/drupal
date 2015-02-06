<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\UserPathUri.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process a path into a 'user-path:' URI.
 *
 * @MigrateProcessPlugin(
 *   id = "userpath_uri"
 * )
 */
class UserPathUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($path) = $value;

    if (parse_url($path, PHP_URL_SCHEME) === NULL) {
      return 'user-path:/' . $path;
    }
    return $path;
  }

}
