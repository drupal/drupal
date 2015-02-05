<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableEntityFormDisplaySubjectTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_drupal\Tests\MigrateDrupalTestBase;

/**
 * Upgrade comment subject variable to core.entity_form_display.comment.*.default.yml
 *
 * @group migrate_drupal
 */
class MigrateCommentVariableEntityFormDisplaySubjectTest extends MigrateDrupalTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  static $modules = array('comment');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
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
    $migration = entity_load('migration', 'd6_comment_entity_form_display_subject');
    $dumps = array(
      $this->getDumpDirectory() . '/Variable.php',
      $this->getDumpDirectory() . '/NodeType.php',
    );
    $this->prepare($migration, $dumps);
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
  }

  /**
   * Tests comment subject variable migrated into an entity display.
   */
  public function testCommentEntityFormDisplay() {
    $component = entity_get_form_display('comment', 'comment', 'default')
      ->getComponent('subject');
    $this->assertIdentical($component['type'], 'string_textfield');
    $this->assertIdentical($component['weight'], 10);
    $component = entity_get_form_display('comment', 'comment_no_subject', 'default')
      ->getComponent('subject');
    $this->assertNull($component);
  }

}
