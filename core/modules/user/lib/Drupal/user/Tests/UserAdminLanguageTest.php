<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserAdminLanguageTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests users' ability to change their own administration language.
 */
class UserAdminLanguageTest extends WebTestBase {

  /**
   * Administrator user for this test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Non-administrator user for this test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $regularUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'language');

  public static function getInfo() {
    return array(
      'name' => 'User administration pages language settings',
      'description' => "Tests user's ability to change their administration pages language.",
      'group' => 'User',
    );
  }

  public function setUp() {
    parent::setUp();
    // User to add and remove language.
    $this->adminUser = $this->drupalCreateUser(array('administer languages', 'access administration pages'));
    // User to check non-admin access.
    $this->regularUser = $this->drupalCreateUser();
  }

  /**
   * Tests that admin language is not configurable in single language sites.
   */
  function testUserAdminLanguageConfigurationNotAvailableWithOnlyOneLanguage() {
    $this->drupalLogin($this->adminUser);
    $this->setLanguageNegotiation();
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is not available.
    $this->assertNoFieldByXPath($this->constructFieldXpath('id', 'edit-preferred-admin-langcode'), NULL, 'Administration pages language selector not available.');
  }

  /**
   * Tests that admin language negotiation is configurable only if enabled.
   */
  function testUserAdminLanguageConfigurationAvailableWithAdminLanguageNegotiation() {
    $this->drupalLogin($this->adminUser);
    $this->addCustomLanguage();
    $path = 'user/' . $this->adminUser->id() . '/edit';

    // Checks with user administration pages language negotiation disabled.
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is not available.
    $this->assertNoFieldByXPath($this->constructFieldXpath('id', 'edit-preferred-admin-langcode'), NULL, 'Administration pages language selector not available.');

    // Checks with user administration pages language negotiation enabled.
    $this->setLanguageNegotiation();
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is available.
    $this->assertFieldByXPath($this->constructFieldXpath('id', 'edit-preferred-admin-langcode'), NULL, 'Administration pages language selector is available.');
  }

  /**
   * Tests that the admin language is configurable only for administrators.
   *
   * If a user has the permission "access administration pages", they should
   * be able to see the setting to pick the language they want those pages in.
   *
   * If a user does not have that permission, it would confusing for them to
   * have a setting for pages they cannot access, so they should not be able to
   * set a language for those pages.
   */
  function testUserAdminLanguageConfigurationAvailableIfAdminLanguageNegotiationIsEnabled() {
    $this->drupalLogin($this->adminUser);
    // Adds a new language, because with only one language, setting won't show.
    $this->addCustomLanguage();
    $this->setLanguageNegotiation();
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    // Ensure administration pages language setting is visible for admin.
    $this->assertFieldByXPath($this->constructFieldXpath('id', 'edit-preferred-admin-langcode'), NULL, 'Administration pages language selector available for admins.');

    // Ensure administration pages language setting is hidden for non-admins.
    $this->drupalLogin($this->regularUser);
    $path = 'user/' . $this->regularUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertNoFieldByXPath($this->constructFieldXpath('id', 'edit-preferred-admin-langcode'), NULL, 'Administration pages language selector not available for regular user.');
  }

  /**
   * Sets the User interface negotiation detection method.
   *
   * Enables the "Account preference for administration pages" language
   * detection method for the User interface language negotiation type.
   */
  function setLanguageNegotiation() {
    $edit = array(
      'language_interface[enabled][language-user-admin]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-user-admin]' => -8,
      'language_interface[weight][language-url]' => -10,
    );
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
  }

  /**
   * Helper method for adding a custom language.
   */
  function addCustomLanguage() {
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
  }

}
