<?php

/**
 * @file
 * Contains \Drupal\user\Tests\Views\AccessTestBase.
 */

namespace Drupal\user\Tests\Views;

/**
 * A common test base class for the user access plugin tests.
 */
abstract class AccessTestBase extends UserTestBase {

  /**
   * Contains a user object that can access all views.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Contains a user object that has no special permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * Contains a user object that has the 'views_test_data test permission'.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $normalUser;

  /**
   * Contains a role ID that is used by the webUser.
   *
   * @var string
   */
  protected $webRole;

  /**
   * Contains a role ID that is used by the normalUser.
   *
   * @var string
   */
  protected $normalRole;

  protected function setUp() {
    parent::setUp();

    $this->enableViewsTestModule();

    $this->adminUser = $this->drupalCreateUser(array('access all views'));
    $this->webUser = $this->drupalCreateUser();
    $roles = $this->webUser->getRoles();
    $this->webRole = $roles[0];

    $this->normalRole = $this->drupalCreateRole(array());
    $this->normalUser = $this->drupalCreateUser(array('views_test_data test permission'));
    $this->normalUser->addRole($this->normalRole);
    $this->normalUser->save();
    // @todo when all the plugin information is cached make a reset function and
    // call it here.
  }
}
