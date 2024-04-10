<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d7;

/**
 * Tests the migration of used files.
 *
 * @group file
 */
class MigrateFileGetIdsTest extends MigrateFileTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'file_test_get_ids'];

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo() {
    $migration_info = parent::getFileMigrationInfo();
    $migration_info['plugin_id'] = 'd7_file_used';
    return $migration_info;
  }

}
