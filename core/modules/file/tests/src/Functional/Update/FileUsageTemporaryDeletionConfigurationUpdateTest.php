<?php

namespace Drupal\Tests\file\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for setting the file usage deletion configuration.
 *
 * @see https://www.drupal.org/node/2801777
 *
 * @group Update
 */
class FileUsageTemporaryDeletionConfigurationUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that block context mapping is updated properly.
   */
  public function testUpdateHookN() {
    $this->assertIdentical($this->config('file.settings')->get('make_unused_managed_files_temporary'), NULL);
    $this->runUpdates();
    $this->assertIdentical($this->config('file.settings')->get('make_unused_managed_files_temporary'), FALSE);
    $this->assertResponse('200');
  }

}
