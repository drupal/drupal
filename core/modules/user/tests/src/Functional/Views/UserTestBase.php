<?php

namespace Drupal\Tests\user\Functional\Views;

use Drupal\Tests\views\Functional\ViewTestBase;
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
  protected static $modules = ['user_test_views', 'node'];

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

  protected function setUp($import_test_views = TRUE, $modules = ['user_test_views']) {
    parent::setUp($import_test_views, $modules);

    $this->users[] = $this->drupalCreateUser();
    $this->users[] = User::load(1);
    $this->nodes[] = $this->drupalCreateNode(['uid' => $this->users[0]->id()]);
    $this->nodes[] = $this->drupalCreateNode(['uid' => 1]);
  }

}
