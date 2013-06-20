<?php

/**
 * @file
 * Contains \Drupal\views_ui\Tests\UITestBase.
 */

namespace Drupal\views_ui\Tests;

use Drupal\views\Tests\ViewTestBase;

/**
 * Provides a base class for testing the Views UI.
 */
abstract class UITestBase extends ViewTestBase {

  /**
   * An admin user with the 'administer views' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * An admin user with administrative permissions for views, blocks, and nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $fullAdminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'block');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(array('administer views'));

    $this->fullAdminUser = $this->drupalCreateUser(array('administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view all revisions'));
    $this->drupalLogin($this->fullAdminUser);
  }

}
