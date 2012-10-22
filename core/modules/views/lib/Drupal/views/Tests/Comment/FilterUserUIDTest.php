<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Comment\FilterUserUIDTest.
 */

namespace Drupal\views\Tests\Comment;

/**
 * Tests the filter_comment_user_uid handler.
 *
 * The actual stuff is done in the parent class.
 */
class FilterUserUIDTest extends CommentTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Comment: User UID Filter',
      'description' => 'Tests the user posted or commented filter handler.',
      'group' => 'Views Modules',
    );
  }

  function testCommentUserUIDTest() {
    $this->view->setItem('default', 'argument', 'uid_touch', NULL);

    $options = array(
      'id' => 'uid_touch',
      'table' => 'node',
      'field' => 'uid_touch',
      'value' => array($this->loggedInUser->uid),
    );
    $this->view->addItem('default', 'filter', 'node', 'uid_touch', $options);
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
