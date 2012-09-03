<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\UserTest.
 */

namespace Drupal\views\Tests\User;

/**
 * Tests basic user module integration into views.
 */
class UserTest extends UserTestBase {

  /**
   * Users to use during this test.
   *
   * @var array
   */
  protected $users = array();

  /**
   * Nodes to use during this test.
   *
   * @var array
   */
  protected $nodes = array();

  public static function getInfo() {
    return array(
      'name' => 'User: Basic integration',
      'description' => 'Tests the integration of user.module.',
      'group' => 'Views Modules',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->users[] = $this->drupalCreateUser();
    $this->users[] = user_load(1);
    $this->nodes[] = $this->drupalCreateNode(array('uid' => $this->users[0]->uid));
    $this->nodes[] = $this->drupalCreateNode(array('uid' => 1));
  }

  /**
   * Add a view which has no explicit relationship to the author and check the result.
   *
   * @todo: Remove the following comment once the relationship is required.
   * One day a view will require the relationship so it should still work
   */
  public function testRelationship() {
    $view = $this->createViewFromConfig('test_user_relationship');

    $this->executeView($view);
    $expected = array();
    for ($i = 0; $i <= 1; $i++) {
      $expected[$i] = array(
        'node_title' => $this->nodes[$i]->label(),
        'users_uid' => $this->nodes[$i]->uid,
        'users_name' => $this->users[$i]->name,
      );
    }
    $this->assertIdenticalResultset($view, $expected);
  }

}
