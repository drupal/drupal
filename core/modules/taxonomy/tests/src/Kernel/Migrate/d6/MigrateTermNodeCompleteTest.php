<?php

declare(strict_types=1);

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\node\Entity\Node;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeCompleteTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Remove the classic node table made in setup.
    $this->removeNodeMigrateMapTable(NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC, '6');

    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');

    $this->executeMigration('language');
    $this->migrateUsers(FALSE);
    $this->migrateFields();
    $this->executeMigrations(['d6_node_settings', 'd6_node_complete']);
    $this->migrateTaxonomy();
    // This is a base plugin ID and we want to run all derivatives.
    $this->executeMigrations(['d6_term_node']);
  }

  /**
   * Tests the Drupal 6 term-node association to Drupal 8 migration.
   */
  public function testTermNode(): void {
    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache([1, 2]);

    $nodes = Node::loadMultiple([1, 2]);
    $node = $nodes[1];
    $this->assertCount(1, $node->field_vocabulary_1_i_0_);
    $this->assertSame('1', $node->field_vocabulary_1_i_0_[0]->target_id);
    $node = $nodes[2];
    $this->assertCount(2, $node->field_vocabulary_2_i_1_);
    $this->assertSame('2', $node->field_vocabulary_2_i_1_[0]->target_id);
    $this->assertSame('3', $node->field_vocabulary_2_i_1_[1]->target_id);

    // Tests the Drupal 6 term-node association to Drupal 8 node revisions.
    $this->executeMigrations(['d6_term_node_revision']);

    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(2001);
    $this->assertCount(2, $node->field_vocabulary_3_i_2_);
    $this->assertSame('4', $node->field_vocabulary_3_i_2_[0]->target_id);
    $this->assertSame('5', $node->field_vocabulary_3_i_2_[1]->target_id);
  }

}
