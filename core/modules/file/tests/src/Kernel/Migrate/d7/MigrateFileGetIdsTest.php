<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Migrate\d7;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration of used files.
 */
#[Group('file')]
#[IgnoreDeprecations]
#[RunTestsInSeparateProcesses]
class MigrateFileGetIdsTest extends MigrateFileTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'file_test_get_ids'];

  /**
   * {@inheritdoc}
   */
  protected function fileMigrationSetup(): void {
    $this->expectDeprecation('Class "Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase" as extended by "Drupal\file_test_get_ids\Plugin\migrate\source\d7\FileUsed" is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533564');
    parent::fileMigrationSetup();
  }

  /**
   * {@inheritdoc}
   */
  protected function getFileMigrationInfo(): array {
    $migration_info = parent::getFileMigrationInfo();
    $migration_info['plugin_id'] = 'd7_file_used';
    return $migration_info;
  }

}
