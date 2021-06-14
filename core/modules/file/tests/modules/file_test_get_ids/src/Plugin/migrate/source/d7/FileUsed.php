<?php

namespace Drupal\file_test_get_ids\Plugin\migrate\source\d7;

use Drupal\file\Plugin\migrate\source\d7\File;

/**
 * Drupal 7 file source from database restricted to used files.
 *
 * @MigrateSource(
 *   id = "d7_file_used",
 *   source_module = "file"
 * )
 */
class FileUsed extends File {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // Join on file_usage table to only migrate used files.
    $query->innerJoin('file_usage', 'fu', 'f.fid = fu.fid');

    return $query;
  }

}
