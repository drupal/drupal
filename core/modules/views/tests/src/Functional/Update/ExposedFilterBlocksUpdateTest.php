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

}
