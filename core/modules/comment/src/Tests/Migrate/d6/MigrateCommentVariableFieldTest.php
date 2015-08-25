<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d6\MigrateCommentVariableFieldTest.
 */

namespace Drupal\comment\Tests\Migrate\d6;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment variables to field.storage.node.comment.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentVariableFieldTest extends MigrateDrupal6TestBase {

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
    $this->executeMigration('d6_comment_field');
  }

  /**
   * Tests comment variables migrated into a field entity.
   */
  public function testCommentField() {
    $this->assertTrue(is_object(FieldStorageConfig::load('node.comment')));
  }

}
