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
  protected $defaultTheme = 'stark';

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
    $this->assertSession()->pageTextContains('Name');
    $this->assertSession()->pageTextContains('Created');

    // User does not by default have access to init, mail and status.
    $this->assertSession()->pageTextNotContains('Init');
    $this->assertSession()->pageTextNotContains('Email');
    $this->assertSession()->pageTextNotContains('Status');

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet('test_user_fields_access');

    // Access for init, mail and status is added in hook_entity_field_access().
    $this->assertSession()->pageTextContains('Init');
    $this->assertSession()->pageTextContains('Email');
    $this->assertSession()->pageTextContains('Status');
  }

  /**
   * Tests the user name formatter shows a link to the user when there is
   * access but not otherwise.
   */
  public function testUserNameLink() {
    $test_user = $this->drupalCreateUser();
    $xpath = "//td/a[.='" . $test_user->getAccountName() . "']/@href[.='" . $test_user->toUrl()->toString() . "']";

    $attributes = [
      'title' => 'View user profile.',
    ];
    $link = $test_user->toLink(NULL, 'canonical', ['attributes' => $attributes])->toString();

    // No access, so no link.
    $this->drupalGet('test_user_fields_access');
    $this->assertSession()->pageTextContains($test_user->getAccountName());
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Assign sub-admin role to grant extra access.
    $user = $this->drupalCreateUser(['sub-admin']);
    $this->drupalLogin($user);
    $this->drupalGet('test_user_fields_access');
    $this->assertSession()->elementsCount('xpath', $xpath, 1);
  }

}
