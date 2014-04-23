<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableEntityFormDisplay.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Tests comment variables migrated into an entity display.
 */
class MigrateCommentVariableEntityFormDisplay extends MigrateCommentVariableDisplayBase {

  /**
   * The migration to run.
   */
  const MIGRATION = 'd6_comment_entity_form_display';

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name'  => 'Migrate comment variables to entity form displays,',
      'description'  => 'Upgrade comment variables to entity.form_display.node.*.default.yml',
      'group' => 'Migrate Drupal',
    );
  }

  /**
   * Tests comment variables migrated into an entity display.
   */
  public function testCommentEntityFormDisplay() {
    foreach ($this->types as $type) {
      $component = entity_get_form_display('node', $type, 'default')->getComponent('comment');
      $this->assertEqual($component['type'], 'comment_default');
      $this->assertEqual($component['weight'], 20);
    }
  }

}
