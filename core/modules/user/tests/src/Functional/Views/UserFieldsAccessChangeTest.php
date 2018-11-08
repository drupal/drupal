<?php

namespace Drupal\Tests\user\Functional\Views;

/**
 * Checks changing entity and field access.
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
    $this->drupalGet('test_user_fields_access');

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
    $this->drupalGet('test_user_fields_access');

    // Access for init, mail and status is added in hook_entity_field_access().
    $this->assertText(t('Init'));
    $this->assertText(t('Email'));
    $this->assertText(t('Status'));
  }

  /**
   * Tests the user name formatter shows a link to the user when there is
   * access but not otherwise.
   */
  public function testUserNameLink() {
    $test_user = $this->drupalCreateUser();
    $xpath = "//td/a[.='" . $test_user->getAccountName() . "'][@class='username']/@href[.='" . $test_user->toUrl()->toString() . "']";

    $attributes = [
      'title' => 'View user profile.',
      'class' => 'username',
    ];
    $link = $test_user->toLink(NULL, 'canonical', ['attributes' => $attributes])->toString();

    // No access, so no link.
    $this->drupalGet('test_user_fields_access');
    $this->assertText($test_user->getAccountName(), 'Found user in view');
    $result = $this->xpath($xpath);
    $this->assertEqual(0, count($result), 'User is not a link');

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet('test_user_fields_access');
    $result = $this->xpath($xpath);
    $this->assertEqual(1, count($result), 'User is a link');
  }

}
