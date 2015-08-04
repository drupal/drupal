<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Migrate\d6\MigrateCommentVariableInstanceTest.
 */

namespace Drupal\comment\Tests\Migrate\d6;

use Drupal\migrate_drupal\Tests\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment variables to field.instance.node.*.comment.yml.
 *
 * @group comment
 */
class MigrateCommentVariableInstanceTest extends MigrateDrupal6TestBase {

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
    $this->loadDumps(['Variable.php', 'NodeType.php']);
    $this->executeMigration('d6_comment_field_instance');
  }

  /**
   * Test the migrated field instance values.
   */
  public function testCommentFieldInstance() {
    $node = entity_create('node', array('type' => 'page'));
    $this->assertIdentical(0, $node->comment->status);
    $this->assertIdentical('comment', $node->comment->getFieldDefinition()->getName());
    $settings = $node->comment->getFieldDefinition()->getSettings();
    $this->assertIdentical(4, $settings['default_mode']);
    $this->assertIdentical(50, $settings['per_page']);
    $this->assertIdentical(0, $settings['anonymous']);
    $this->assertIdentical(FALSE, $settings['form_location']);
    $this->assertIdentical(1, $settings['preview']);

    $node = entity_create('node', array('type' => 'story'));
    $this->assertIdentical(2, $node->comment_no_subject->status);
    $this->assertIdentical('comment_no_subject', $node->comment_no_subject->getFieldDefinition()->getName());
    $settings = $node->comment_no_subject->getFieldDefinition()->getSettings();
    $this->assertIdentical(2, $settings['default_mode']);
    $this->assertIdentical(70, $settings['per_page']);
    $this->assertIdentical(1, $settings['anonymous']);
    $this->assertIdentical(FALSE, $settings['form_location']);
    $this->assertIdentical(0, $settings['preview']);
  }

}
