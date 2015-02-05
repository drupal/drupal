<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableInstanceTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade comment variables to field.instance.node.*.comment.yml.
 *
 * @group migrate_drupal
 */
class MigrateCommentVariableInstanceTest extends MigrateDrupalTestBase {

  static $modules = array('comment', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Add some id mappings for the dependant migrations.
    $id_mappings = array(
      'd6_comment_field' => array(
        array(array('page'), array('node', 'page')),
      ),
      'd6_node_type' => array(
        array(array('page'), array('page')),
      ),
    );
    $this->prepareMigrations($id_mappings);

    foreach (array('page', 'story', 'article') as $type) {
      entity_create('node_type', array('type' => $type))->save();
    }
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'comment',
      'type' => 'comment',
      'translatable' => '0',
    ))->save();
    entity_create('field_storage_config', array(
      'entity_type' => 'node',
      'field_name' => 'comment_no_subject',
      'type' => 'comment',
      'translatable' => '0',
    ))->save();
    /** @var \Drupal\migrate\entity\Migration $migration */
    $migration = entity_load('migration', 'd6_comment_field_instance');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
      $this->getDumpDirectory() . '/NodeType.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Test the migrated field instance values.
   */
  public function testCommentFieldInstance() {
    $node = entity_create('node', array('type' => 'page'));
    $this->assertIdentical($node->comment->status, 0);
    $this->assertIdentical($node->comment->getFieldDefinition()->getName(), 'comment');
    $settings = $node->comment->getFieldDefinition()->getSettings();
    $this->assertIdentical($settings['default_mode'], 4);
    $this->assertIdentical($settings['per_page'], 50);
    $this->assertIdentical($settings['anonymous'], 0);
    $this->assertIdentical($settings['form_location'], FALSE);
    $this->assertIdentical($settings['preview'], 1);

    $node = entity_create('node', array('type' => 'story'));
    $this->assertIdentical($node->comment_no_subject->status, 2);
    $this->assertIdentical($node->comment_no_subject->getFieldDefinition()->getName(), 'comment_no_subject');
    $settings = $node->comment_no_subject->getFieldDefinition()->getSettings();
    $this->assertIdentical($settings['default_mode'], 2);
    $this->assertIdentical($settings['per_page'], 70);
    $this->assertIdentical($settings['anonymous'], 1);
    $this->assertIdentical($settings['form_location'], FALSE);
    $this->assertIdentical($settings['preview'], 0);
  }

}
