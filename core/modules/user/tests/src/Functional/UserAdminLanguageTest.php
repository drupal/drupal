<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests users' ability to change their own administration language.
 *
 * @group user
 */
class UserAdminLanguageTest extends BrowserTestBase {

  /**
   * A user with permission to access admin pages and administer languages.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A non-administrator user for this test.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $regularUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'language', 'language_test', 'user_language_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // User to add and remove language.
    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'access administration pages',
    ]);
    // User to check non-admin access.
    $this->regularUser = $this->drupalCreateUser();
  }

  /**
   * Tests that admin language is not configurable in single language sites.
   */
  public function testUserAdminLanguageConfigurationNotAvailableWithOnlyOneLanguage(): void {
    $this->drupalLogin($this->adminUser);
    $this->setLanguageNegotiation();
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is not available.
    $this->assertSession()->fieldNotExists('edit-preferred-admin-langcode');
  }

  /**
   * Tests that admin language negotiation is configurable only if enabled.
   */
  public function testUserAdminLanguageConfigurationAvailableWithAdminLanguageNegotiation(): void {
    $this->drupalLogin($this->adminUser);
    $this->addCustomLanguage();
    $path = 'user/' . $this->adminUser->id() . '/edit';

    // Checks with user administration pages language negotiation disabled.
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is not available.
    $this->assertSession()->fieldNotExists('edit-preferred-admin-langcode');

    // Checks with user administration pages language negotiation enabled.
    $this->setLanguageNegotiation();
    $this->drupalGet($path);
    // Ensure administration pages language settings widget is available.
    $this->assertSession()->fieldExists('edit-preferred-admin-langcode');
  }

  /**
   * Tests that the admin language is configurable only for administrators.
   *
   * If a user has the permission "access administration pages" or
   * "view the administration theme", they should be able to see the setting to
   * pick the language they want those pages in.
   *
   * If a user does not have that permission, it would confusing for them to
   * have a setting for pages they cannot access, so they should not be able to
   * set a language for those pages.
   */
  public function testUserAdminLanguageConfigurationAvailableIfAdminLanguageNegotiationIsEnabled(): void {
    $this->drupalLogin($this->adminUser);
    // Adds a new language, because with only one language, setting won't show.
    $this->addCustomLanguage();
    $this->setLanguageNegotiation();
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    // Ensure administration pages language setting is visible for admin.
    $this->assertSession()->fieldExists('edit-preferred-admin-langcode');

    // Ensure administration pages language setting is visible for editors.
    $editor = $this->drupalCreateUser(['view the administration theme']);
    $this->drupalLogin($editor);
    $path = 'user/' . $editor->id() . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->fieldExists('edit-preferred-admin-langcode');

    // Ensure administration pages language setting is hidden for non-admins.
    $this->drupalLogin($this->regularUser);
    $path = 'user/' . $this->regularUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->fieldNotExists('edit-preferred-admin-langcode');
  }

  /**
   * Tests the actual language negotiation.
   */
  public function testActualNegotiation(): void {
    $this->drupalLogin($this->adminUser);
    $this->addCustomLanguage();
    $this->setLanguageNegotiation();

    // Even though we have admin language negotiation, so long as the user has
    // no preference set, negotiation will fall back further.
    $path = 'user/' . $this->adminUser->id() . '/edit';
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-default');
    $this->drupalGet('xx/' . $path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-url');

    // Set a preferred language code for the user.
    $edit = [];
    $edit['preferred_admin_langcode'] = 'xx';
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save');

    // Test negotiation with the URL method first. The admin method will only
    // be used if the URL method did not match.
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-user-admin');
    $this->drupalGet('xx/' . $path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-url');

    // Test negotiation with the admin language method first. The admin method
    // will be used at all times.
    $this->setLanguageNegotiation(TRUE);
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-user-admin');
    $this->drupalGet('xx/' . $path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-user-admin');

    // Make sure 'language-user-admin' plugin does not fail when a route is
    // restricted to POST requests and language negotiation with the admin
    // language method is used.
    $this->drupalGet('/user-language-test/form');
    $this->submitForm([], 'Send');
    $this->assertSession()->statusCodeEquals(200);

    // Unset the preferred language code for the user.
    $edit = [];
    $edit['preferred_admin_langcode'] = '';
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save');
    $this->drupalGet($path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-default');
    $this->drupalGet('xx/' . $path);
    $this->assertSession()->pageTextContains('Language negotiation method: language-url');
  }

  /**
   * Sets the User interface negotiation detection method.
   *
   * Enables the "Account preference for administration pages" language
   * detection method for the User interface language negotiation type.
   *
   * @param bool $admin_first
   *   Whether the admin negotiation should be first.
   */
  public function setLanguageNegotiation($admin_first = FALSE): void {
    $edit = [
      'language_interface[enabled][language-user-admin]' => TRUE,
      'language_interface[enabled][language-url]' => TRUE,
      'language_interface[weight][language-user-admin]' => ($admin_first ? -12 : -8),
      'language_interface[weight][language-url]' => -10,
    ];
    $this->drupalGet('admin/config/regional/language/detection');
    $this->submitForm($edit, 'Save settings');
  }

  /**
   * Helper method for adding a custom language.
   */
  public function addCustomLanguage(): void {
    $langcode = 'xx';
    // The English name for the language.
    $name = $this->randomMachineName(16);
    $edit = [
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'label' => $name,
      'direction' => LanguageInterface::DIRECTION_LTR,
    ];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add custom language');
  }

}
