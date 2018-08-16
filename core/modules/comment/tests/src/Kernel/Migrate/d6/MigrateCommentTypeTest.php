<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests the migration of comment types from Drupal 6.
 *
 * @group comment
 * @group migrate_drupal_6
 */
class MigrateCommentTypeTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->executeMigration('d6_comment_type');
  }

  /**
   * Asserts a comment type entity.
   *
   * @param string $id
   *   The entity ID.
   * @param string $label
   *   The entity label.
   */
  protected function assertEntity($id, $label) {
    $entity = CommentType::load($id);
    $this->assertInstanceOf(CommentType::class, $entity);
    $this->assertSame($label, $entity->label());
    $this->assertSame('node', $entity->getTargetEntityTypeId());
  }

  /**
   * Tests the migrated comment types.
   */
  public function testMigration() {
    $this->assertEntity('comment_node_article', 'Article comment');
    $this->assertEntity('comment_node_company', 'Company comment');
    $this->assertEntity('comment_node_employee', 'Employee comment');
    $this->assertEntity('comment_node_event', 'Event comment');
    $this->assertEntity('comment_forum', 'Forum topic comment');
    $this->assertEntity('comment_node_page', 'Page comment');
    $this->assertEntity('comment_node_sponsor', 'Sponsor comment');
    $this->assertEntity('comment_node_story', 'Story comment');
    $this->assertEntity('comment_node_test_event', 'Migrate test event comment');
    $this->assertEntity('comment_node_test_page', 'Migrate test page comment');
    $this->assertEntity('comment_node_test_planet', 'Migrate test planet comment');
    $this->assertEntity('comment_node_test_story', 'Migrate test story comment');
  }

}
