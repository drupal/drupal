<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateTermNodeTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->migrateContent();
    $this->migrateTaxonomy();
    $this->executeMigrations(['d6_term_node:*']);
  }

  /**
   * Tests the Drupal 6 term-node association to Drupal 8 migration.
   */
  public function testTermNode() {
    $this->container->get('entity.manager')
      ->getStorage('node')
      ->resetCache([1, 2]);

    $nodes = Node::loadMultiple([1, 2]);
    $node = $nodes[1];
    $this->assertIdentical(1, count($node->vocabulary_1_i_0_));
    $this->assertIdentical('1', $node->vocabulary_1_i_0_[0]->target_id);
    $node = $nodes[2];
    $this->assertIdentical(2, count($node->vocabulary_2_i_1_));
    $this->assertIdentical('2', $node->vocabulary_2_i_1_[0]->target_id);
    $this->assertIdentical('3', $node->vocabulary_2_i_1_[1]->target_id);
  }

}
