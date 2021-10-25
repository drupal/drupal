<?php

namespace Drupal\Tests\taxonomy\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * @group taxonomy
 */
class MigrateNodeTaxonomyTest extends MigrateDrupal7TestBase {

  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'image',
    'link',
    'menu_ui',
    'node',
    'taxonomy',
    'telephone',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');

    $this->migrateTaxonomyTerms();
    $this->migrateUsers(FALSE);
    $this->executeMigration('d7_node:article');
  }

  /**
   * Tests node migration from Drupal 7 to 8.
   */
  public function testMigration() {
    $node = Node::load(2);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertEquals(9, $node->field_tags[0]->target_id);
    $this->assertEquals(14, $node->field_tags[1]->target_id);
    $this->assertEquals(17, $node->field_tags[2]->target_id);
  }

}
