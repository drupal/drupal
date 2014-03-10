<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\ArgumentUserUIDTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the argument_comment_user_uid handler.
 */
class ArgumentUserUIDTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_comment_user_uid');

  public static function getInfo() {
    return array(
      'name' => 'Comment: User UID Argument',
      'description' => 'Tests the user posted or commented argument handler.',
      'group' => 'Views module integration',
    );
  }

  function testCommentUserUIDTest() {
    $view = Views::getView('test_comment_user_uid');
    $this->executeView($view, array($this->account->id()));
    $result_set = array(
      array(
        'nid' => $this->node_user_posted->id(),
      ),
      array(
        'nid' => $this->node_user_commented->id(),
      ),
    );
    $column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $result_set, $column_map);
  }

}
