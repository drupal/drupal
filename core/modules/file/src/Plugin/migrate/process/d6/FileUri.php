<?php

namespace Drupal\file\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the file URL into a D8 compatible URL.
 *
 * @MigrateProcessPlugin(
 *   id = "file_uri"
 * )
 */
class FileUri extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a uri of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }
    [$filepath, $file_directory_path, $temp_directory_path, $is_public] = $value;

    // Specific handling using $temp_directory_path for temporary files.
    if (substr($filepath, 0, strlen($temp_directory_path)) === $temp_directory_path) {
      $uri = preg_replace('/^' . preg_quote($temp_directory_path, '/') . '/', '', $filepath);
      return 'temporary://' . ltrim($uri, '/');
    }

    // Strip the files path from the uri instead of using basename
    // so any additional folders in the path are preserved.
    $uri = preg_replace('/^' . preg_quote($file_directory_path, '/') . '/', '', $filepath);

    return ($is_public ? 'public' : 'private') . '://' . ltrim($uri, '/');
  }

}
