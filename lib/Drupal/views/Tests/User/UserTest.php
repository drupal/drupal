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

}
