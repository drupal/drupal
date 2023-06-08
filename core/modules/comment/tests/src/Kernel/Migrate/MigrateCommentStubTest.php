<?php

namespace Drupal\Tests\comment\Kernel\Migrate;

use Drupal\comment\Entity\CommentType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;
use Drupal\migrate_drupal\Tests\StubTestTrait;
use Drupal\node\Entity\NodeType;

/**
 * Test stub creation for comment entities.
 *
 * @group comment
 */
class MigrateCommentStubTest extends MigrateDrupalTestBase {

  use StubTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installEntitySchema('node');

    // Make sure uid 0 is created (default uid for comments is 0).
    $storage = \Drupal::entityTypeManager()->getStorage('user');
    // Insert a row for the anonymous user.
    $storage
      ->create([
        'uid' => 0,
        'status' => 0,
        'name' => '',
      ])
      ->save();
    // Need at least one node type and comment type present.
    NodeType::create([
      'type' => 'testnodetype',
      'name' => 'Test node type',
    ])->save();
    CommentType::create([
      'id' => 'testcommenttype',
      'label' => 'Test comment type',
      'target_entity_type_id' => 'node',
    ])->save();
  }

  /**
   * Tests creation of comment stubs.
   */
  public function testStub() {
    $this->performStubTest('comment');
  }

}
