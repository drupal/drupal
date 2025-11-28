<?php

namespace Drupal\file\Plugin\migrate\process\d6;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process the file URL into a D8 compatible URL.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess('file_uri')]
class FileUri extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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
    if (str_starts_with($filepath, $temp_directory_path)) {
      $uri = preg_replace('/^' . preg_quote($temp_directory_path, '/') . '/', '', $filepath);
      return 'temporary://' . ltrim($uri, '/');
    }

    // Strip the files path from the uri instead of using basename
    // so any additional folders in the path are preserved.
    $uri = preg_replace('/^' . preg_quote($file_directory_path, '/') . '/', '', $filepath);

    return ($is_public ? 'public' : 'private') . '://' . ltrim($uri, '/');
  }

}
