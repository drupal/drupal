<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\Views\FilterUserUIDTest.
 */

namespace Drupal\comment\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the filter_comment_user_uid handler.
 *
 * The actual stuff is done in the parent class.
 */
class FilterUserUIDTest extends CommentTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_comment_user_uid');

  public static function getInfo() {
    return array(
      'name' => 'Comment: User UID Filter',
      'description' => 'Tests the user posted or commented filter handler.',
      'group' => 'Views module integration',
    );
  }

  function testCommentUserUIDTest() {
    $view = Views::getView('test_comment_user_uid');
    $view->setDisplay();
    $view->removeHandler('default', 'argument', 'uid_touch');

    $options = array(
      'id' => 'uid_touch',
      'table' => 'node_field_data',
      'field' => 'uid_touch',
      'value' => array($this->loggedInUser->id()),
    );
    $view->addHandler('default', 'filter', 'node_field_data', 'uid_touch', $options);
    $this->executeView($view, array($this->account->id()));
    $result_set = array(
      array(
        'nid' => $this->node_user_posted->id(),
      ),
      array(
        'nid' => $this->node_user_commented->id(),
      ),
    );
    $this->column_map = array('nid' => 'nid');
    $this->assertIdenticalResultset($view, $result_set, $this->column_map);
  }

}
