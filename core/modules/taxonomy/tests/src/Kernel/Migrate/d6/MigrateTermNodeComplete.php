<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d6;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\node\Entity\Node;

/**
 * Upgrade taxonomy term node associations.
 *
 * @group migrate_drupal_6
 */
class MigrateTermNodeComplete extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'content_translation',
    'language',
    'menu_ui',
    // A requirement for d6_node_translation.
    'migrate_drupal_multilingual',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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
  public function testTermNode() {
    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->resetCache([1, 2]);

    $nodes = Node::loadMultiple([1, 2]);
    $node = $nodes[1];
    $this->assertSame(1, count($node->field_vocabulary_1_i_0_));
    $this->assertSame('1', $node->field_vocabulary_1_i_0_[0]->target_id);
    $node = $nodes[2];
    $this->assertSame(2, count($node->field_vocabulary_2_i_1_));
    $this->assertSame('2', $node->field_vocabulary_2_i_1_[0]->target_id);
    $this->assertSame('3', $node->field_vocabulary_2_i_1_[1]->target_id);
  }

}
