<?php

namespace Drupal\Tests\comment\Kernel\Migrate\d6;

use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Upgrade comment variables to entity.display.node.*.default.yml.
 *
 * @group migrate_drupal_6
 */
class MigrateCommentVariableEntityDisplayTest extends MigrateCommentVariableDisplayBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->executeMigration('d6_comment_entity_display');
  }

  /**
   * Tests comment variables migrated into an entity display.
   */
  public function testCommentEntityDisplay() {
    foreach (['page', 'story', 'article'] as $type) {
      $component = EntityViewDisplay::load('node.' . $type . '.default')->getComponent('comment');
      $this->assertIdentical('hidden', $component['label']);
      $this->assertIdentical('comment_default', $component['type']);
      $this->assertIdentical(20, $component['weight']);
    }
  }
}
