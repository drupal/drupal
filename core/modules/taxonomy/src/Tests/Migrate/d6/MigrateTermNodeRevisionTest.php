<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Migrate\d6\MigrateTermNodeRevisionTest.
 */

namespace Drupal\taxonomy\Tests\Migrate\d6;

use Drupal\migrate\Entity\Migration;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeRevisionTest extends MigrateTermNodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $id_mappings = array(
      'd6_term_node:*' => array(
        array(array(2), array(1)),
      ),
      'd6_node_revision:*' => array(
        array(array(2), array(2)),
      ),
    );
    $this->prepareMigrations($id_mappings);

    $migrations = Migration::loadMultiple(['d6_term_node_revision:*']);
    array_walk($migrations, [$this, 'executeMigration']);
  }

  /**
   * Tests the Drupal 6 term-node revision association to Drupal 8 migration.
   */
  public function testTermRevisionNode() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    $this->assertIdentical(2, count($node->vocabulary_3_i_2_));
    $this->assertIdentical('4', $node->vocabulary_3_i_2_[0]->target_id);
    $this->assertIdentical('5', $node->vocabulary_3_i_2_[1]->target_id);
  }

}
