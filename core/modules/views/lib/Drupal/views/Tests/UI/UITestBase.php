<?php

/**
 * @file
 * Definition of Drupal\views\Tests\UI\UITestBase.
 */

namespace Drupal\views\Tests\UI;

use Drupal\views\Tests\ViewTestBase;

/**
 * Provides a base class for testing the Views UI.
 */
abstract class UITestBase extends ViewTestBase {

  /**
   * An admin user with the 'administer views' permission.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
   */
  protected $adminUser;

  /**
   * An admin user with administrative permissions for views, blocks, and nodes.
   *
   * @var \Drupal\user\Plugin\Core\Entity\User
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
