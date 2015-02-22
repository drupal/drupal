<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\process\d6\InternalUri.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process a path into an 'internal:' URI.
 *
 * @MigrateProcessPlugin(
 *   id = "internal_uri"
 * )
 */
class InternalUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    list($path) = $value;

    if (parse_url($path, PHP_URL_SCHEME) === NULL) {
      return 'internal:/' . $path;
    }
    return $path;
  }

}
