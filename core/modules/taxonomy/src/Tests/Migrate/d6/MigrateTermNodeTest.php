<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateTermNodeTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\node\Entity\Node;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group taxonomy
 */
class MigrateTermNodeTest extends MigrateTermNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migrations = entity_load_multiple('migration', array('d6_term_node:*'));
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, $this);
      $executable->import();
    }
  }

  /**
   * Tests the Drupal 6 term-node association to Drupal 8 migration.
   */
  public function testTermNode() {
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $node_storage->resetCache(array(1, 2));
    $nodes = Node::loadMultiple(array(1, 2));
    $node = $nodes[1];
    $this->assertIdentical(1, count($node->vocabulary_1_i_0_));
    $this->assertIdentical('1', $node->vocabulary_1_i_0_[0]->target_id);
    $node = $nodes[2];
    $this->assertIdentical(2, count($node->vocabulary_2_i_1_));
    $this->assertIdentical('2', $node->vocabulary_2_i_1_[0]->target_id);
    $this->assertIdentical('3', $node->vocabulary_2_i_1_[1]->target_id);
  }

}
