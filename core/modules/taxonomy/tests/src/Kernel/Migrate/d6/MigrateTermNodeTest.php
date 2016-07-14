<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
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
  }

  /**
   * Tests the Drupal 6 term-node association to Drupal 8 migration.
   */
  public function testTermNode() {
    // This is a base plugin id and we want to run all derivatives.
    $this->executeMigrations(['d6_term_node']);

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

  /**
   * Tests that term associations are ignored when they belong to nodes which
   * were not migrated.
   */
  public function testSkipNonExistentNode() {
    // Node 2 is migrated by d6_node__story, but we need to pretend that it
    // failed, so record that in the map table.
    $this->mockFailure('d6_node:story', ['nid' => 2, 'language' => 'en']);

    // d6_term_node__2 should skip over node 2 (a.k.a. revision 3) because,
    // according to the map table, it failed.
    $migration = $this->getMigration('d6_term_node:2');
    $this->executeMigration($migration);
    $this->assertNull($migration->getIdMap()->lookupDestinationId(['vid' => 3])[0]);
  }

}
