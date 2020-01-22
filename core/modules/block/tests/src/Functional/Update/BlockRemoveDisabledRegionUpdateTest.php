<?php

namespace Drupal\Tests\block\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for removal of disabled region.
 *
 * @see https://www.drupal.org/node/2513534
 *
 * @group Update
 * @group legacy
 */
class BlockRemoveDisabledRegionUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block_test', 'language'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.update-test-block-disabled-2513534.php',
    ];
  }

  /**
   * Tests that block context mapping is updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    // Disable maintenance mode.
    \Drupal::state()->set('system.maintenance_mode', FALSE);

    // We finished updating so we can login the user now.
    $this->drupalLogin($this->rootUser);

    // Verify that a disabled block is in the default region.
    $this->drupalGet('admin/structure/block');
    $element = $this->xpath("//tr[contains(@data-drupal-selector, :block) and contains(@class, :status)]//select/option[@selected and @value=:region]",
      [':block' => 'edit-blocks-pagetitle-1', ':status' => 'block-disabled', ':region' => 'header']);
    $this->assertTrue(!empty($element));

    // Verify that an enabled block is now disabled and in the default region.
    $this->drupalGet('admin/structure/block');
    $element = $this->xpath("//tr[contains(@data-drupal-selector, :block) and contains(@class, :status)]//select/option[@selected and @value=:region]",
      [':block' => 'edit-blocks-pagetitle-2', ':status' => 'block-disabled', ':region' => 'header']);
    $this->assertTrue(!empty($element));

  }

}
