<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d6\MigrateCommentTypeTest.
 */

namespace Drupal\comment\Tests\Migrate\d6;

use Drupal\comment\Entity\CommentType;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment type.
 *
 * @group comment
 */
class MigrateCommentTypeTest extends MigrateDrupal6TestBase {

  static $modules = array('node', 'comment', 'text', 'filter');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installConfig(['node', 'comment']);

    $this->loadDumps(['Variable.php', 'NodeType.php']);
    $this->executeMigration('d6_comment_type');
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment type migration.
   */
  public function testCommentType() {
    $comment_type = CommentType::load('comment');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
    $comment_type = CommentType::load('comment_no_subject');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
  }

}
