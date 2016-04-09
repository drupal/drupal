<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\comment\CommentManagerInterface;
use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;
use Drupal\node\Entity\Node;

/**
 * Upgrade comment variables to field.instance.node.*.comment.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentVariableInstanceTest extends MigrateDrupal6TestBase {

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
    $this->migrateContentTypes();
    $this->executeMigrations([
      'd6_comment_type',
      'd6_comment_field',
      'd6_comment_field_instance',
    ]);
  }

  /**
   * Test the migrated field instance values.
   */
  public function testCommentFieldInstance() {
    $node = Node::create(['type' => 'page']);
    $this->assertIdentical(0, $node->comment->status);
    $this->assertIdentical('comment', $node->comment->getFieldDefinition()->getName());
    $settings = $node->comment->getFieldDefinition()->getSettings();
    $this->assertIdentical(CommentManagerInterface::COMMENT_MODE_THREADED, $settings['default_mode']);
    $this->assertIdentical(50, $settings['per_page']);
    $this->assertFalse($settings['anonymous']);
    $this->assertFalse($settings['form_location']);
    $this->assertTrue($settings['preview']);

    $node = Node::create(['type' => 'story']);
    $this->assertIdentical(2, $node->comment_no_subject->status);
    $this->assertIdentical('comment_no_subject', $node->comment_no_subject->getFieldDefinition()->getName());
    $settings = $node->comment_no_subject->getFieldDefinition()->getSettings();
    $this->assertIdentical(CommentManagerInterface::COMMENT_MODE_FLAT, $settings['default_mode']);
    $this->assertIdentical(70, $settings['per_page']);
    $this->assertTrue($settings['anonymous']);
    $this->assertFalse($settings['form_location']);
    $this->assertFalse($settings['preview']);
  }

}
