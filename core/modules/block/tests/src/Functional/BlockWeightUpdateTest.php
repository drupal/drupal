<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\block\Entity\Block;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @covers block_post_update_make_weight_integer
 * @group block
 */
class BlockWeightUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests update path for blocks' `weight` property.
   */
  public function testRunUpdates() {
    // Find a block and change it to have a null weight.
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');
    $block = $database->select('config', 'c')
      ->fields('c', ['data'])
      ->condition('name', 'block.block.claro_content')
      ->execute()
      ->fetchField();
    $block = unserialize($block);
    $block['weight'] = NULL;
    $database->update('config')
      ->fields([
        'data' => serialize($block),
      ])
      ->condition('name', 'block.block.claro_content')
      ->execute();

    $this->assertNull(Block::load('claro_content')->get('weight'));
    $this->runUpdates();
    $this->assertSame(0, Block::load('claro_content')->get('weight'));
  }

}
