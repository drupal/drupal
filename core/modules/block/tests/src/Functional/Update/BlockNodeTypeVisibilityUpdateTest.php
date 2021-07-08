<?php

namespace Drupal\Tests\block\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for updating the node type visibility condition.
 *
 * @group Update
 */
class BlockNodeTypeVisibilityUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that block context mapping is updated properly.
   *
   * @group legacy
   */
  public function testBlock() {
    $bundles = [
      'article' => 'article',
      'test_content_type' => 'test_content_type',
    ];

    $block = \Drupal::config('block.block.stark_testblock');
    $this->assertEquals($bundles, $block->get('visibility.node_type.bundles'));
    $this->assertNull($block->get('visibility.entity_bundle:node'));

    $this->runUpdates();

    $block = \Drupal::config('block.block.stark_testblock');
    $this->assertEquals($bundles, $block->get('visibility.entity_bundle:node.bundles'));
    $this->assertEquals('entity_bundle:node', $block->get('visibility.entity_bundle:node.id'));
    $this->assertNull($block->get('visibility.node_type'));
  }

}
