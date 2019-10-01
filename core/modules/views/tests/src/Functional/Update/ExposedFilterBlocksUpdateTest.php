<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the additional settings are added to the entity link field.
 *
 * @see views_post_update_entity_link_url()
 *
 * @group legacy
 */
class ExposedFilterBlocksUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/exposed-filter-blocks.php',
    ];
  }

  /**
   * Tests that exposed filter blocks label display are disabled.
   */
  public function testViewsPostUpdateExposedFilterBlocks() {
    $this->runUpdates();

    // Assert the label display has been disabled after the update.
    $block = Block::load('exposedformtest_exposed_blockpage_1');
    $config = $block->getPlugin()->getConfiguration();
    $this->assertEquals('0', $config['label_display']);
  }

  /**
   * Tests that the update succeeds even if Block is not installed.
   */
  public function testViewsPostUpdateExposedFilterBlocksWithoutBlock() {
    // This block is created during the update process, but since we are
    // uninstalling the Block module for this test, it will fail config schema
    // validation. Since that's okay for the purposes of this test, just make
    // the config schema checker ignore the block.
    static::$configSchemaCheckerExclusions[] = 'block.block.seven_secondary_local_tasks';

    // We need to uninstall the menu_link_content module because
    // menu_link_content_entity_predelete() invokes alias processing and we
    // don't have a working path alias system until system_update_8803() runs.
    // Note that path alias processing is disabled during the regular database
    // update process, so this only happens because we uninstall the Block
    // module before running the updates.
    // @see \Drupal\Core\Update\UpdateServiceProvider::alter()
    $this->container->get('module_installer')->uninstall(['menu_link_content', 'block']);
    $this->runUpdates();
  }

}
