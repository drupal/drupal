<?php

/**
 * @file
 * Contains \Drupal\tracker\Tests\Views\TrackerUserUidTest.
 */

namespace Drupal\tracker\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the tracker user uid handlers.
 */
class TrackerUserUidTest extends TrackerTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_tracker_user_uid');

  public static function getInfo() {
    return array(
      'name' => 'Tracker: User UID tests',
      'description' => 'Tests the tracker comment user uid handlers.',
      'group' => 'Views module integration',
    );
  }

  /**
   * Tests the user uid filter and argument.
   */
  public function testUserUid() {
    $map = array(
      'nid' => 'nid',
      'node_field_data_title' => 'title',
    );

    $expected = array(
      array(
        'nid' => $this->node->id(),
        'title' => $this->node->label(),
      )
    );

    $view = Views::getView('test_tracker_user_uid');
    $this->executeView($view);

    // We should have no results as the filter is set for uid 0.
    $this->assertIdenticalResultSet($view, array(), $map);
    $view->destroy();

    // Change the filter value to our user.
    $view->initHandlers();
    $view->filter['uid_touch_tracker']->value = $this->node->getOwnerId();
    $this->executeView($view);

    // We should have one result as the filter is set for the created user.
    $this->assertIdenticalResultSet($view, $expected, $map);
    $view->destroy();

    // Remove the filter now, so only the argument will affect the query.
    $view->removeHandler('default', 'filter', 'uid_touch_tracker');

    // Test the incorrect argument UID.
    $view->initHandlers();
    $this->executeView($view, array(rand()));
    $this->assertIdenticalResultSet($view, array(), $map);
    $view->destroy();

    // Test the correct argument UID.
    $view->initHandlers();
    $this->executeView($view, array($this->node->getOwnerId()));
    $this->assertIdenticalResultSet($view, $expected, $map);
  }

}
