<?php

/**
 * @file
 * Definition of Drupal\views\Tests\User\UserTestBase.
 */

namespace Drupal\views\Tests\User;

use Drupal\views\Tests\ViewTestBase;

/**
 * @todo.
 */
abstract class UserTestBase extends ViewTestBase {

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

  protected function setUp() {
    parent::setUp();

    $this->users[] = $this->drupalCreateUser();
    $this->users[] = user_load(1);
    $this->nodes[] = $this->drupalCreateNode(array('uid' => $this->users[0]->uid));
    $this->nodes[] = $this->drupalCreateNode(array('uid' => 1));
  }

}
