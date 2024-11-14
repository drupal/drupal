<?php

declare(strict_types=1);

namespace Drupal\migrate_prepare_row_test\Hook;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for migrate_prepare_row_test.
 */
class MigratePrepareRowTestHooks {

  /**
   * Implements hook_migrate_prepare_row().
   */
  #[Hook('migrate_prepare_row')]
  public function migratePrepareRow(Row $row, MigrateSourceInterface $source, MigrationInterface $migration) {
    // Test both options for save_to_map.
    $data = $row->getSourceProperty('data');
    if ($data == 'skip_and_record') {
      // Record mapping but don't record a message.
      throw new MigrateSkipRowException('', TRUE);
    }
    elseif ($data == 'skip_and_do_not_record') {
      // Don't record mapping but record a message.
      throw new MigrateSkipRowException('skip_and_do_not_record message', FALSE);
    }
  }

}
