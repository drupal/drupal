<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\UserTestBase.
 */

namespace Drupal\user\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * @todo.
 */
abstract class UserTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user_test_views', 'node');

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

    ViewTestData::createTestViews(get_class($this), array('user_test_views'));

    $this->users[] = $this->drupalCreateUser();
    $this->users[] = user_load(1);
    $this->nodes[] = $this->drupalCreateNode(array('uid' => $this->users[0]->id()));
    $this->nodes[] = $this->drupalCreateNode(array('uid' => 1));
  }

}
