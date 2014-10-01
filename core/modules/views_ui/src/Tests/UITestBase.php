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
  public static $modules = array('node', 'views_ui', 'block', 'taxonomy');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(array('administer views'));

    $this->fullAdminUser = $this->drupalCreateUser(array('administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view all revisions'));
    $this->drupalLogin($this->fullAdminUser);
  }

  /**
   * A helper method which creates a random view.
   */
  public function randomView(array $view = array()) {
    // Create a new view in the UI.
    $default = array();
    $default['label'] = $this->randomMachineName(16);
    $default['id'] = strtolower($this->randomMachineName(16));
    $default['description'] = $this->randomMachineName(16);
    $default['page[create]'] = TRUE;
    $default['page[path]'] = $default['id'];

    $view += $default;

    $this->drupalPostForm('admin/structure/views/add', $view, t('Save and edit'));

    return $default;
  }

}
