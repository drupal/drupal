<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests removing blocks placed using the removed Syndicate block plugin.
 *
 * @see block_post_update_remove_syndicate_blocks()
 */
#[Group('Update')]
#[Group('block')]
#[CoversFunction('block_post_update_remove_syndicate_blocks')]
#[RunTestsInSeparateProcesses]
class BlockRemoveSyndicateBlocksUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/syndicate-block-config.php',
    ];
  }

  /**
   * Tests that node module Syndicate blocks are deleted in all themes.
   */
  public function testRemoveSyndicateBlocks(): void {
    $config_factory = $this->container->get('config.factory');
    $syndicate_blocks = [
      'block.block.olivero_syndicate',
      'block.block.stark_syndicate',
    ];
    foreach ($syndicate_blocks as $name) {
      $this->assertSame('node_syndicate_block', $config_factory->get($name)->get('plugin'));
    }
    $this->assertSame('custom_syndicate', $config_factory->get('block.block.stark_custom_syndicate')->get('settings.provider'));
    $this->assertFalse($config_factory->get('block.block.stark_powered')->isNew());

    $this->runUpdates();

    $config_factory = $this->container->get('config.factory');
    foreach ($syndicate_blocks as $name) {
      $this->assertTrue($config_factory->get($name)->isNew(), "$name was deleted.");
    }
    // A block using the same plugin ID from another provider is kept, which
    // allows a contrib module to take over the block before the update.
    $this->assertFalse($config_factory->get('block.block.stark_custom_syndicate')->isNew());
    // Other blocks are not affected.
    $this->assertFalse($config_factory->get('block.block.stark_powered')->isNew());
  }

}
