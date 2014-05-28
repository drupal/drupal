<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\Process\d6\system_update_7000.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\process\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Decide if it's an image or a file when coming from a D6 filefield.
 *
 * @MigrateProcessPlugin(
 *   id = "file_image_handler"
 * )
 */
class FileImageHandler extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {

    // If it's an array then the map missed.
    if (is_array($value)) {

      // Filefields in D6 have no way to tell if it's an image or file so we
      // have to look at the widget type as well.
      if ($row->getSourceProperty('module') == 'filefield') {
        $widget_type = $row->getSourceProperty('widget_type');
        $value = $widget_type == "imagefield_widget" ? "image" : "file";
      }
      else {
        throw new MigrateSkipRowException();
      }
    }

    return $value;
  }

}
