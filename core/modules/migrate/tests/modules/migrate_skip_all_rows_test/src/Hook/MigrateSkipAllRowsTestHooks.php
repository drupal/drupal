<?php

declare(strict_types=1);

namespace Drupal\migrate_skip_all_rows_test\Hook;

use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateSourceInterface;
use Drupal\migrate\Row;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for migrate_skip_all_rows_test.
 */
class MigrateSkipAllRowsTestHooks {

  /**
   * Implements hook_migrate_prepare_row().
   */
  #[Hook('migrate_prepare_row')]
  public function migratePrepareRow(Row $row, MigrateSourceInterface $source, MigrationInterface $migration): void {
    if (\Drupal::state()->get('migrate_skip_all_rows_test_migrate_prepare_row')) {
      throw new MigrateSkipRowException();
    }
  }

}
