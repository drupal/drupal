<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for a user's ability to change their default language.
 *
 * @group user
 */
class UserLanguageTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test if user can change their default language.
   */
  public function testUserLanguageConfiguration() {
    // User to add and remove language.
    $admin_user = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    // User to change their default language.
    $web_user = $this->drupalCreateUser();

    // Add custom language.
    $this->drupalLogin($admin_user);
    // Code for the language.
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, 'Add custom language');
    $this->drupalLogout();

    // Log in as normal user and edit account settings.
    $this->drupalLogin($web_user);
    $path = 'user/' . $web_user->id() . '/edit';
    $this->drupalGet($path);
    // Ensure language settings widget is available.
    $this->assertText('Language', 'Language selector available.');
    // Ensure custom language is present.
    $this->assertText($name, 'Language present on form.');
    // Switch to our custom language.
    $edit = [
      'preferred_langcode' => $langcode,
    ];
    $this->drupalPostForm($path, $edit, 'Save');
    // Ensure form was submitted successfully.
    $this->assertText('The changes have been saved.', 'Changes were saved.');
    // Check if language was changed.
    $this->assertTrue($this->assertSession()->optionExists('edit-preferred-langcode', $langcode)->isSelected());

    $this->drupalLogout();
  }

}
