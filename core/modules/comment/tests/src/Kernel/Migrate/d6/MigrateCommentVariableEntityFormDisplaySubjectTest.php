<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Upgrade comment subject variable to core.entity_form_display.comment.*.default.yml
 *
 * @group migrate_drupal_6
 */
class MigrateCommentVariableEntityFormDisplaySubjectTest extends MigrateDrupal6TestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['comment']);
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_entity_form_display_subject',
    ]);
  }

  /**
   * Tests comment subject variable migrated into an entity display.
   */
  public function testCommentEntityFormDisplay() {
    $component = EntityFormDisplay::load('comment.comment.default')
      ->getComponent('subject');
    $this->assertIdentical('string_textfield', $component['type']);
    $this->assertIdentical(10, $component['weight']);
    $component = EntityFormDisplay::load('comment.comment_no_subject.default')
      ->getComponent('subject');
    $this->assertNull($component);
  }

}
