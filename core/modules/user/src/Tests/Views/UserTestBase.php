<?php

namespace Drupal\user\Tests\Views;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\user\Entity\User;

/**
 * @todo.
 */
abstract class UserTestBase extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_test_views', 'node'];

  /**
   * Users to use during this test.
   *
   * @var array
   */
  protected $users = [];

  /**
   * Nodes to use during this test.
   *
   * @var array
   */
  protected $nodes = [];

  protected function setUp() {
    parent::setUp();

    ViewTestData::createTestViews(get_class($this), ['user_test_views']);

    $this->users[] = $this->drupalCreateUser();
    $this->users[] = User::load(1);
    $this->nodes[] = $this->drupalCreateNode(['uid' => $this->users[0]->id()]);
    $this->nodes[] = $this->drupalCreateNode(['uid' => 1]);
  }

}
