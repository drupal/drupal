<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentTypeTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment type.
 *
 * @group migrate_drupal
 */
class MigrateCommentTypeTest extends MigrateDrupal6TestBase {

  static $modules = array('node', 'comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment_type');

    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
      $this->getDumpDirectory() . '/NodeType.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests the Drupal 6 to Drupal 8 comment type migration.
   */
  public function testCommentType() {
    $comment_type = entity_load('comment_type', 'comment');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
    $comment_type = entity_load('comment_type', 'comment_no_subject');
    $this->assertIdentical('node', $comment_type->getTargetEntityTypeId());
  }

}
