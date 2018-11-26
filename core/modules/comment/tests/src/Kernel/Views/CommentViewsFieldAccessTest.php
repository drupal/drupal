<?php

namespace Drupal\Tests\comment\Kernel\Views;

use Drupal\comment\Entity\Comment;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\user\Entity\User;
use Drupal\Tests\views\Kernel\Handler\FieldFieldAccessTestBase;

/**
 * Tests base field access in Views for the comment entity.
 *
 * @group comment
 */
class CommentViewsFieldAccessTest extends FieldFieldAccessTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['comment', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('comment');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Check access for comment fields.
   */
  public function testCommentFields() {
    $user = User::create([
      'name' => 'test user',
    ]);
    $user->save();

    $host = EntityTest::create(['name' => $this->randomString()]);
    $host->save();

    $comment = Comment::create([
      'subject' => 'My comment title',
      'uid' => $user->id(),
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
    ]);
    $comment->save();

    $comment_anonymous = Comment::create([
      'subject' => 'Anonymous comment title',
      'uid' => 0,
      'name' => 'anonymous',
      'mail' => 'test@example.com',
      'homepage' => 'https://example.com',
      'entity_type' => 'entity_test',
      'field_name' => 'comment',
      'entity_id' => $host->id(),
      'comment_type' => 'entity_test',
      'created' => 123456,
      'status' => 1,
    ]);
    $comment_anonymous->save();

    // @todo Expand the test coverage in https://www.drupal.org/node/2464635

    $this->assertFieldAccess('comment', 'cid', $comment->id());
    $this->assertFieldAccess('comment', 'cid', $comment_anonymous->id());
    $this->assertFieldAccess('comment', 'uuid', $comment->uuid());
    $this->assertFieldAccess('comment', 'subject', 'My comment title');
    $this->assertFieldAccess('comment', 'subject', 'Anonymous comment title');
    $this->assertFieldAccess('comment', 'name', 'anonymous');
    $this->assertFieldAccess('comment', 'mail', 'test@example.com');
    $this->assertFieldAccess('comment', 'homepage', 'https://example.com');
    $this->assertFieldAccess('comment', 'uid', $user->getAccountName());
    // $this->assertFieldAccess('comment', 'created', \Drupal::service('date.formatter')->format(123456));
    // $this->assertFieldAccess('comment', 'changed', \Drupal::service('date.formatter')->format(REQUEST_TIME));
    $this->assertFieldAccess('comment', 'status', 'On');
  }

}
