<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Tests\d6\MigrateCommentVariableEntityFormDisplayTest.
 */

namespace Drupal\migrate_drupal\Tests\d6;

/**
 * Upgrade comment variables to core.entity_form_display.node.*.default.yml.
 *
 * @group migrate_drupal
 */
class MigrateCommentVariableEntityFormDisplayTest extends MigrateCommentVariableDisplayBase {

  /**
   * The migration to run.
   */
  const MIGRATION = 'd6_comment_entity_form_display';

  /**
   * Tests comment variables migrated into an entity display.
   */
  public function testCommentEntityFormDisplay() {
    foreach ($this->types as $type) {
      $component = entity_get_form_display('node', $type, 'default')->getComponent('comment');
      $this->assertIdentical($component['type'], 'comment_default');
      $this->assertIdentical($component['weight'], 20);
    }
  }

}
