<?php

/**
 * @file
 * Tests for menu language settings.
 */

namespace Drupal\menu\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\Language;

/**
 * Defines a test class for testing menu language functionality.
 */
class MenuLanguageTest extends MenuWebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language');

  protected $admin_user;
  protected $menu;

  public static function getInfo() {
    return array(
      'name' => 'Menu language',
      'description' => 'Create menu and menu links in non-English language, and edit language settings.',
      'group' => 'Menu',
    );
  }

  function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer menu'));
    $this->drupalLogin($this->admin_user);

    // Add some custom languages.
    foreach (array('aa', 'bb', 'cc', 'cs') as $language_code) {
      $language = new Language(array(
        'id' => $language_code,
        'name' => $this->randomName(),
      ));
      language_save($language);
    }
  }

  /**
   * Tests menu language settings and the defaults for menu link items.
   */
  function testMenuLanguage() {
    // Create a test menu to test the various language-related settings.
    // Machine name has to be lowercase.
    $menu_name = Unicode::strtolower($this->randomName(16));
    $label = $this->randomString();
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' =>  $label,
      'langcode' => 'aa',
      'default_language[langcode]' => 'bb',
      'default_language[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Check that the language settings were saved.
    $this->assertEqual(entity_load('menu', $menu_name)->langcode, $edit['langcode']);
    $language_settings = language_get_default_configuration('menu_link', $menu_name);
    $this->assertEqual($language_settings['langcode'], 'bb');
    $this->assertEqual($language_settings['language_show'], TRUE);

    // Check menu language and item language configuration.
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], 'The menu language was correctly selected.');
    $this->assertOptionSelected('edit-default-language-langcode', $edit['default_language[langcode]'], 'The menu link default language was correctly selected.');
    $this->assertFieldChecked('edit-default-language-language-show');

    // Test menu link language.
    $link_path = '<front>';

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = array(
      'link_title' => $link_title,
      'link_path' => $link_path,
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name/add", $edit, t('Save'));
    // Check the link was added with the correct menu link default language.
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_title' => $link_title));
    $menu_link = reset($menu_links);
    $this->assertMenuLink($menu_link->id(), array(
      'menu_name' => $menu_name,
      'link_path' => $link_path,
      'langcode' => 'bb',
    ));

    // Edit menu link default, changing it to cc.
    $edit = array(
      'default_language[langcode]' => 'cc',
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name", $edit, t('Save'));

    // Check cc is the menu link default.
    $this->assertOptionSelected('edit-default-language-langcode', $edit['default_language[langcode]'], 'The menu link default language was correctly selected.');

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = array(
      'link_title' => $link_title,
      'link_path' => $link_path,
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name/add", $edit, t('Save'));
    // Check the link was added with the correct new menu link default language.
    $menu_links = entity_load_multiple_by_properties('menu_link', array('link_title' => $link_title));
    $menu_link = reset($menu_links);
    $this->assertMenuLink($menu_link->id(), array(
      'menu_name' => $menu_name,
      'link_path' => $link_path,
      'langcode' => 'cc',
    ));

    // Now change the language of the new link to 'bb'.
    $edit = array(
      'langcode' => 'bb',
    );
    $this->drupalPostForm('admin/structure/menu/item/' . $menu_link->id() . '/edit', $edit, t('Save'));
    $this->assertMenuLink($menu_link->id(), array(
      'menu_name' => $menu_name,
      'link_path' => $link_path,
      'langcode' => 'bb',
    ));

    // Saving menu link items ends up on the edit menu page. To check the menu
    // link has the correct language default on edit, go to the menu link edit
    // page first.
    $this->drupalGet('admin/structure/menu/item/' . $menu_link->id() . '/edit');
    // Check that the language selector has the correct default value.
    $this->assertOptionSelected('edit-langcode', 'bb', 'The menu link language was correctly selected.');

    // Edit menu to hide the language select on menu link item add.
    $edit = array(
      'default_language[language_show]' => FALSE,
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name", $edit, t('Save'));
    $this->assertNoFieldChecked('edit-default-language-language-show');

    // Check that the language settings were saved.
    $language_settings = language_get_default_configuration('menu_link', $menu_name);
    $this->assertEqual($language_settings['langcode'], 'cc');
    $this->assertEqual($language_settings['language_show'], FALSE);

    // Check that the language selector is not available on menu link add page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertNoField('edit-langcode', 'The language selector field was hidden the page');
  }

  /**
   * Tests menu configuration is still English after English has been deleted.
   */
  function testMenuLanguageRemovedEnglish() {
    // Create a test menu to test language settings.
    // Machine name has to be lowercase.
    $menu_name = Unicode::strtolower($this->randomName(16));
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' => $this->randomString(),
      'langcode' => 'en',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Check that the language settings were saved.
    $menu = menu_load($menu_name);
    $this->assertEqual($menu->langcode, 'en');

    // Remove English language. To do that another language has to be set as
    // default.
    $language = language_load('cs');
    $language->default = TRUE;
    language_save($language);
    language_delete('en');

    // Save the menu again and check if the language is still the same.
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name", array(), t('Save'));
    $menu = menu_load($menu_name);
    $this->assertEqual($menu->langcode, 'en');
  }

}
