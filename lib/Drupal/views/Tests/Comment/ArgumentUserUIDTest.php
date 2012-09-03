<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\ArgumentUserUIDTest.
 */

namespace Drupal\views\Tests\Comment;

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
    $this->executeView($this->view, array($this->account->uid));
    $result_set = array(
      array(
        'nid' => $this->node_user_posted->nid,
      ),
      array(
        'nid' => $this->node_user_commented->nid,
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($this->view, $result_set, $this->column_map);
  }

}
