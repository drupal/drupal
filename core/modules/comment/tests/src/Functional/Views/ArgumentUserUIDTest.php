<?php

namespace Drupal\Tests\comment\Functional\Views;

use Drupal\comment\Entity\Comment;
use Drupal\user\Entity\User;
use Drupal\views\Views;

/**
 * Tests the user posted or commented argument handler.
 *
 * @group comment
 */
class ArgumentUserUIDTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_comment_user_uid'];

  public function testCommentUserUIDTest() {
    // Add an additional comment which is not created by the user.
    $new_user = User::create(['name' => 'new user']);
    $new_user->save();

    $comment = Comment::create([
      'uid' => $new_user->uid->value,
      'entity_id' => $this->nodeUserCommented->id(),
      'entity_type' => 'node',
      'field_name' => 'comment',
      'subject' => 'if a woodchuck could chuck wood.',
    ]);
    $comment->save();

    $view = Views::getView('test_comment_user_uid');
    $this->executeView($view, [$this->account->id()]);
    $result_set = [
      [
        'nid' => $this->nodeUserPosted->id(),
      ],
      [
        'nid' => $this->nodeUserCommented->id(),
      ],
    ];
    $column_map = ['nid' => 'nid'];
    $this->assertIdenticalResultset($view, $result_set, $column_map);
  }

}
