<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateTermNodeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal
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
    $nodes = node_load_multiple(array(1, 2), TRUE);
    $node = $nodes[1];
    $this->assertEqual(count($node->vocabulary_1_i_0_), 1);
    $this->assertEqual($node->vocabulary_1_i_0_[0]->target_id, 1);
    $node = $nodes[2];
    $this->assertEqual(count($node->vocabulary_2_i_1_), 2);
    $this->assertEqual($node->vocabulary_2_i_1_[0]->target_id, 2);
    $this->assertEqual($node->vocabulary_2_i_1_[1]->target_id, 3);
  }

}
