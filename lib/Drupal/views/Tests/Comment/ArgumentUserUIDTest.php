<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\ArgumentUserUIDTest.
 */

namespace Drupal\views\Tests\Comment;

use Drupal\views\View;

/**
 * Tests the argument_comment_user_uid handler.
 */
class ArgumentUserUIDTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Comment: User UID Argument',
      'description' => 'Tests the user posted or commented argument handler.',
      'group' => 'Views Modules',
    );
  }

  function testCommentUserUIDTest() {
    $view = $this->view_comment_user_uid();

    $this->executeView($view, array($this->account->uid));
    $result_set = array(
      array(
        'nid' => $this->node_user_posted->nid,
      ),
      array(
        'nid' => $this->node_user_commented->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $result_set, $this->column_map);
  }

}
