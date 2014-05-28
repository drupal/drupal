<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableField.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Tests comment variables migrated into a field entity.
 */
class MigrateCommentVariableField extends MigrateDrupalTestBase {

  static $modules = array('comment', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate comment variables to a field,',
      'description'  => 'Upgrade comment variables  to field.field.node.comment.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    foreach (array('page', 'story', 'test') as $type) {
      entity_create('node_type', array('type' => $type))->save();
    }
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment_field');
    $dumps = array(
      $this->getDumpDirectory() . '/Drupal6CommentVariable.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests comment variables migrated into a field entity.
   */
  public function testCommentField() {
    $this->assertTrue(is_object(entity_load('field_config', 'node.comment')));
  }

}
