<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeRevisionTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->migrateContent(['revisions']);
    $this->migrateTaxonomy();
    $this->executeMigrations(['d6_term_node', 'd6_term_node_revision']);
  }

  /**
   * Tests the Drupal 6 term-node revision association to Drupal 8 migration.
   */
  public function testTermRevisionNode() {
    $node = \Drupal::entityManager()->getStorage('node')->loadRevision(2);
    $this->assertSame(2, count($node->field_vocabulary_3_i_2_));
    $this->assertSame('4', $node->field_vocabulary_3_i_2_[0]->target_id);
    $this->assertSame('5', $node->field_vocabulary_3_i_2_[1]->target_id);
  }

}
