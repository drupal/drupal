<?php

namespace Drupal\Tests\user\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests whether proper language is stored for new users and access to language
 * selector.
 *
 * @group user
 */
class UserLanguageCreationTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Functional test for language handling during user creation.
   */
  public function testLocalUserCreation() {
    // User to add and remove language and create new users.
    $admin_user = $this->drupalCreateUser(['administer languages', 'access administration pages', 'administer users']);
    $this->drupalLogin($admin_user);

    // Add predefined language.
    $langcode = 'fr';
    ConfigurableLanguage::createFromLangcode($langcode)->save();

    // Set language negotiation.
    $edit = [
      'language_interface[enabled][language-url]' => TRUE,
    ];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
    $this->assertText(t('Language detection configuration saved.'), 'Set language negotiation.');

    // Check if the language selector is available on admin/people/create and
    // set to the currently active language.
    $this->drupalGet($langcode . '/admin/people/create');
    $this->assertOptionSelected("edit-preferred-langcode", $langcode, 'Global language set in the language selector.');

    // Create a user with the admin/people/create form and check if the correct
    // language is set.
    $username = $this->randomMachineName(10);
    $edit = [
      'name' => $username,
      'mail' => $this->randomMachineName(4) . '@example.com',
      'pass[pass1]' => $username,
      'pass[pass2]' => $username,
    ];

    $this->drupalPostForm($langcode . '/admin/people/create', $edit, t('Create new account'));

    $user = user_load_by_name($username);
    $this->assertEqual($user->getPreferredLangcode(), $langcode, 'New user has correct preferred language set.');
    $this->assertEqual($user->language()->getId(), $langcode, 'New user has correct profile language set.');

    // Register a new user and check if the language selector is hidden.
    $this->drupalLogout();

    $this->drupalGet($langcode . '/user/register');
    $this->assertNoFieldByName('language[fr]', 'Language selector is not accessible.');

    $username = $this->randomMachineName(10);
    $edit = [
      'name' => $username,
      'mail' => $this->randomMachineName(4) . '@example.com',
    ];

    $this->drupalPostForm($langcode . '/user/register', $edit, t('Create new account'));

    $user = user_load_by_name($username);
    $this->assertEqual($user->getPreferredLangcode(), $langcode, 'New user has correct preferred language set.');
    $this->assertEqual($user->language()->getId(), $langcode, 'New user has correct profile language set.');

    // Test if the admin can use the language selector and if the
    // correct language is was saved.
    $user_edit = $langcode . '/user/' . $user->id() . '/edit';

    $this->drupalLogin($admin_user);
    $this->drupalGet($user_edit);
    $this->assertOptionSelected("edit-preferred-langcode", $langcode, 'Language selector is accessible and correct language is selected.');

    // Set passRaw so we can log in the new user.
    $user->passRaw = $this->randomMachineName(10);
    $edit = [
      'pass[pass1]' => $user->passRaw,
      'pass[pass2]' => $user->passRaw,
    ];

    $this->drupalPostForm($user_edit, $edit, t('Save'));

    $this->drupalLogin($user);
    $this->drupalGet($user_edit);
    $this->assertOptionSelected("edit-preferred-langcode", $langcode, 'Language selector is accessible and correct language is selected.');
  }

}
