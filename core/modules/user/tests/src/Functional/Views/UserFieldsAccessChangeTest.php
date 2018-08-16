<?php

namespace Drupal\Tests\user\Functional\Views;

/**
 * Checks if user fields access permissions can be modified by other modules.
 *
 * @group user
 */
class UserFieldsAccessChangeTest extends UserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_access_test'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_user_fields_access'];

  /**
   * Tests if another module can change field access.
   */
  public function testUserFieldAccess() {
    $path = 'test_user_fields_access';
    $this->drupalGet($path);

    // User has access to name and created date by default.
    $this->assertText(t('Name'));
    $this->assertText(t('Created'));

    // User does not by default have access to init, mail and status.
    $this->assertNoText(t('Init'));
    $this->assertNoText(t('Email'));
    $this->assertNoText(t('Status'));

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet($path);

    // Access for init, mail and status is added in hook_entity_field_access().
    $this->assertText(t('Init'));
    $this->assertText(t('Email'));
    $this->assertText(t('Status'));
  }

}
