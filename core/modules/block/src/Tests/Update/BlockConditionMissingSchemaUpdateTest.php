<?php

namespace Drupal\block\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for block with conditions missing context.
 *
 * @see https://www.drupal.org/node/2811519
 *
 * @group Update
 */
class BlockConditionMissingSchemaUpdateTest extends UpdatePathTestBase {

  /**
   * This test does not have a failed update but the configuration has missing
   * schema so can not do the full post update testing offered by
   * UpdatePathTestBase.
   *
   * @var bool
   *
   * @see \Drupal\system\Tests\Update\UpdatePathTestBase::runUpdates()
   */
  protected $checkFailedUpdates = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.block-test-enabled-missing-schema.php',
    ];
  }

  /**
   * Tests that block context mapping is updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();
    $this->drupalGet('<front>');
    // If the block is fixed by block_post_update_fix_negate_in_conditions()
    // then it will be visible.
    $this->assertText('Test missing schema on conditions');
  }

}
