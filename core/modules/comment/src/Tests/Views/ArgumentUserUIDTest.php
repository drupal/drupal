<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\ArgumentUserUIDTest.
 */

namespace Drupal\comment\Tests\Views;

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
  public static $testViews = array('test_comment_user_uid');

  function testCommentUserUIDTest() {
    $view = Views::getView('test_comment_user_uid');
    $this->executeView($view, array($this->account->id()));
    $result_set = array(
      array(
        'nid' => $this->nodeUserPosted->id(),
      ),
      array(
        'nid' => $this->nodeUserCommented->id(),
      ),
    );
    $column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $result_set, $column_map);
  }

}
