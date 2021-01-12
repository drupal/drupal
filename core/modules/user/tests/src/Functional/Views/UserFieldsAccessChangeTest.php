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
  protected static $modules = ['user_access_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
    $this->assertText('Name');
    $this->assertText('Created');

    // User does not by default have access to init, mail and status.
    $this->assertNoText('Init');
    $this->assertNoText('Email');
    $this->assertNoText('Status');

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet('test_user_fields_access');

    // Access for init, mail and status is added in hook_entity_field_access().
    $this->assertText('Init');
    $this->assertText('Email');
    $this->assertText('Status');
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
    $this->assertCount(0, $result, 'User is not a link');

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet('test_user_fields_access');
    $result = $this->xpath($xpath);
    $this->assertCount(1, $result, 'User is a link');
  }

}
