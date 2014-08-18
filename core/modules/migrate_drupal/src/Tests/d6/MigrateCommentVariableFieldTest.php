<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableFieldTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade comment variables to field.storage.node.comment.yml.
 *
 * @group migrate_drupal
 */
class MigrateCommentVariableFieldTest extends MigrateDrupalTestBase {

  static $modules = array('comment', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    foreach (array('page', 'story', 'test') as $type) {
      entity_create('node_type', array('type' => $type))->save();
    }
    foreach (['comment', 'comment_no_subject'] as $comment_type) {
      entity_create('comment_type', array(
        'id' => $comment_type,
        'target_entity_type_id' => 'node',
      ))
      ->save();
    }
    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_comment_type' => array(
        array(array('comment'), array('comment_no_subject')),
      ),
    );
    $this->prepareMigrations($id_mappings);
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
    $this->assertTrue(is_object(entity_load('field_storage_config', 'node.comment')));
  }

}
