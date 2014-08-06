<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserAdminLanguageTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests users' ability to change their own administration language.
 *
 * @group user
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
  public static $modules = array('user', 'language', 'language_test');

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
   * Tests the actual language negotiation.
   */
  function testActualNegotiation() {
    $this->drupalLogin($this->adminUser);
    $this->addCustomLanguage();
    $this->setLanguageNegotiation();

    // Even though we have admin language negotiation, so long as the user has
    // no preference set, negotiation will fall back further.
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertText('Language negotiation method: language-default');

    // Set a preferred language code for the user.
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $edit = array();
    $edit['preferred_admin_langcode'] = 'xx';
    $this->drupalPostForm($path, $edit, t('Save'));

    // Test negotiation with the URL method first. The admin method will only
    // be used if the URL method did not match.
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertText('Language negotiation method: language-user-admin');
    $path = 'xx/user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertText('Language negotiation method: language-url');

    // Test negotiation with the admin language method first. The admin method
    // will be used at all times.
    $this->setLanguageNegotiation(TRUE);
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertText('Language negotiation method: language-user-admin');
    $path = 'xx/user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertText('Language negotiation method: language-user-admin');
  }

  /**
   * Sets the User interface negotiation detection method.
   *
   * @param bool $admin_first
   *   Whether the admin negotiation should be first.
   *
   * Enables the "Account preference for administration pages" language
   * detection method for the User interface language negotiation type.
   */
  function setLanguageNegotiation($admin_first = FALSE) {
    $edit = array(
      'language_interface[enabled][language-user-admin]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-user-admin]' => ($admin_first ? -12 : -8),
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
    $name = $this->randomMachineName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    );
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add custom language'));
  }

}
