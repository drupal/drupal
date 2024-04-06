<?php

namespace Drupal\migrate_prepare_row_test\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a testing process plugin that skips rows.
 */
#[MigrateProcess('test_skip_row_process')]
class TestSkipRowProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Test both options for save_to_map.
    $data = $row->getSourceProperty('data');
    if ($data == 'skip_and_record (use plugin)') {
      throw new MigrateSkipRowException('', TRUE);
    }
    elseif ($data == 'skip_and_do_not_record (use plugin)') {
      throw new MigrateSkipRowException('', FALSE);
    }
    return $value;
  }

}
