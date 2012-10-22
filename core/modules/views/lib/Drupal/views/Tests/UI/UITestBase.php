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
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'block');

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(array('administer views'));

    $views_admin = $this->drupalCreateUser(array('administer views', 'administer blocks', 'bypass node access', 'access user profiles', 'view revisions'));
    $this->drupalLogin($views_admin);
  }

}
