<?php

namespace Drupal\Tests\forum\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\node\Kernel\Migrate\d6\MigrateNodeTestBase;

/**
 * Tests forum migration from Drupal 6 to Drupal 8.
 *
 * @group migrate_drupal_6
 */
class MigrateForumTest extends MigrateNodeTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'comment',
    'forum',
    'menu_ui',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('forum', ['forum', 'forum_index']);
    $this->installConfig(['comment', 'forum']);
    $this->migrateContent();
    $this->migrateTaxonomy();
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
      'd6_comment_entity_display',
      'd6_comment_entity_form_display',
      'd6_comment',
      'd6_term_node',
    ]);
  }

  /**
   * Tests forum migration.
   */
  public function testForumMigration() {
    // Tests that the taxonomy_forums field storage config exists.
    $field_storage_config = FieldStorageConfig::load('node.taxonomy_forums');
    $this->assertInstanceOf(FieldStorageConfig::class, $field_storage_config);

    // Tests that the taxonomy_forums field config exists.
    $field_config = FieldConfig::load('node.forum.taxonomy_forums');
    $this->assertInstanceOf(FieldConfig::class, $field_config);

    // Tests that the taxonomy_forums entity view display component exists.
    $entity_view_display = EntityViewDisplay::load('node.forum.default')->getComponent('taxonomy_forums');
    $this->assertIsArray($entity_view_display);

    // Tests that the taxonomy_forums entity form display component exists.
    $entity_form_display = EntityFormDisplay::load('node.forum.default')->getComponent('taxonomy_forums');
    $this->assertIsArray($entity_form_display);

    // Test that the taxonomy_forums field has the right value.
    $node = Node::load(19);
    $this->assertEquals(8, $node->taxonomy_forums->target_id);
  }

}
