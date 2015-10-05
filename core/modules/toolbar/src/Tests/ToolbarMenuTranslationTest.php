<?php

/**
 * @file
 * Contains \Drupal\toolbar\Tests\ToolbarMenuTranslationTest.
 */

namespace Drupal\toolbar\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that the toolbar icon class remains for translated menu items.
 *
 * @group toolbar
 */
class ToolbarMenuTranslationTest extends WebTestBase {

  /**
   * A user with permission to access the administrative toolbar.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('toolbar', 'toolbar_test', 'locale', 'locale_test');

  protected function setUp() {
    parent::setUp();

    // Create an administrative user and log it in.
    $this->adminUser = $this->drupalCreateUser(array('access toolbar', 'translate interface', 'administer languages', 'access administration pages'));
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that toolbar classes don't change when adding a translation.
   */
  function testToolbarClasses() {
    $langcode = 'es';

    // Add Spanish.
    $edit['predefined_langcode'] = $langcode;
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // The menu item 'Structure' in the toolbar will be translated.
    $menu_item = 'Structure';

    // Visit a page that has the string on it so it can be translated.
    $this->drupalGet($langcode . '/admin/structure');

    // Search for the menu item.
    $search = array(
      'string' => $menu_item,
      'langcode' => $langcode,
      'translation' => 'untranslated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Make sure will be able to translate the menu item.
    $this->assertNoText('No strings available.', 'Search found the menu item as untranslated.');

    // Check that the class is on the item before we translate it.
    $xpath = $this->xpath('//a[contains(@class, "icon-system-admin-structure")]');
    $this->assertEqual(count($xpath), 1, 'The menu item class ok before translation.');

    // Translate the menu item.
    $menu_item_translated = $this->randomMachineName();
    $textarea = current($this->xpath('//textarea'));
    $lid = (string) $textarea[0]['name'];
    $edit = array(
      $lid => $menu_item_translated,
    );
    $this->drupalPostForm('admin/config/regional/translate', $edit, t('Save translations'));

    // Search for the translated menu item.
    $search = array(
      'string' => $menu_item,
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPostForm('admin/config/regional/translate', $search, t('Filter'));
    // Make sure the menu item string was translated.
    $this->assertText($menu_item_translated, 'Search found the menu item as translated: ' . $menu_item_translated . '.');

    // Go to another page in the custom language and make sure the menu item
    // was translated.
    $this->drupalGet($langcode . '/admin/structure');
    $this->assertText($menu_item_translated, 'Found the menu translated.');

    // Toolbar icons are included based on the presence of a specific class on
    // the menu item. Ensure that class also exists for a translated menu item.
    $xpath = $this->xpath('//a[contains(@class, "icon-system-admin-structure")]');
    $this->assertEqual(count($xpath), 1, 'The menu item class is the same.');
  }

}
