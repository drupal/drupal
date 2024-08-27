<?php

declare(strict_types=1);

namespace Drupal\Tests\toolbar\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the toolbar icon class remains for translated menu items.
 *
 * @group toolbar
 */
class ToolbarMenuTranslationTest extends BrowserTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'toolbar',
    'toolbar_test',
    'locale',
    'locale_test',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser([
      'access toolbar',
      'translate interface',
      'administer languages',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that toolbar classes don't change when adding a translation.
   */
  public function testToolbarClasses(): void {
    $langcode = 'es';

    // Add Spanish.
    $edit['predefined_langcode'] = $langcode;
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

    // The menu item 'Structure' in the toolbar will be translated.
    $menu_item = 'Structure';

    // Visit a page that has the string on it so it can be translated.
    $this->drupalGet($langcode . '/admin/structure');

    // Search for the menu item.
    $search = [
      'string' => $menu_item,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // Make sure will be able to translate the menu item.
    $this->assertSession()->pageTextNotContains('No strings available.');

    // Check that the class is on the item before we translate it.
    $this->assertSession()->elementsCount('xpath', '//a[contains(@class, "icon-system-admin-structure")]', 1);

    // Translate the menu item.
    $menu_item_translated = $this->randomMachineName();
    $textarea = $this->assertSession()->elementExists('xpath', '//textarea');
    $lid = (string) $textarea->getAttribute('name');
    $edit = [
      $lid => $menu_item_translated,
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($edit, 'Save translations');

    // Search for the translated menu item.
    $search = [
      'string' => $menu_item,
      'langcode' => $langcode,
      'translation' => 'translated',
    ];
    $this->drupalGet('admin/config/regional/translate');
    $this->submitForm($search, 'Filter');
    // Make sure the menu item string was translated.
    $this->assertSession()->pageTextContains($menu_item_translated);

    // Go to another page in the custom language and make sure the menu item
    // was translated.
    $this->drupalGet($langcode . '/admin/structure');
    $this->assertSession()->pageTextContains($menu_item_translated);

    // Toolbar icons are included based on the presence of a specific class on
    // the menu item. Ensure that class also exists for a translated menu item.
    $this->assertSession()->elementsCount('xpath', '//a[contains(@class, "icon-system-admin-structure")]', 1);
  }

}
