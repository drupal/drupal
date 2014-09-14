<?php

/**
 * @file
 * Tests for menu_ui language settings.
 */

namespace Drupal\menu_ui\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\system\Entity\Menu;

/**
 * Create menu and menu links in non-English language, and edit language
 * settings.
 *
 * @group menu_ui
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

  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array('access administration pages', 'administer menu'));
    $this->drupalLogin($this->admin_user);

    // Add some custom languages.
    foreach (array('aa', 'bb', 'cc', 'cs') as $language_code) {
      ConfigurableLanguage::create(array(
        'id' => $language_code,
        'label' => $this->randomMachineName(),
      ))->save();
    }
  }

  /**
   * Tests menu language settings and the defaults for menu link items.
   */
  function testMenuLanguage() {
    // Create a test menu to test the various language-related settings.
    // Machine name has to be lowercase.
    $menu_name = Unicode::strtolower($this->randomMachineName(16));
    $label = $this->randomString();
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' =>  $label,
      'langcode' => 'aa',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));
    language_save_default_configuration('menu_link_content', 'menu_link_content',  array('langcode' => 'bb', 'language_show' => TRUE));

    // Check menu language.
    $this->assertOptionSelected('edit-langcode', $edit['langcode'], 'The menu language was correctly selected.');

    // Test menu link language.
    $link_path = '<front>';

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $link_title,
      'url' => $link_path,
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name/add", $edit, t('Save'));
    // Check the link was added with the correct menu link default language.
    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $link_title));
    $menu_link = reset($menu_links);
    $this->assertMenuLink($menu_link->getPluginId(), array(
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'bb',
    ));

    // Edit menu link default, changing it to cc.
    language_save_default_configuration('menu_link_content', 'menu_link_content',  array('langcode' => 'cc', 'language_show' => TRUE));

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $link_title,
      'url' => $link_path,
    );
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name/add", $edit, t('Save'));
    // Check the link was added with the correct new menu link default language.
    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $link_title));
    $menu_link = reset($menu_links);
    $this->assertMenuLink($menu_link->getPluginId(), array(
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'cc',
    ));

    // Now change the language of the new link to 'bb'.
    $edit = array(
      'langcode' => 'bb',
    );
    $this->drupalPostForm('admin/structure/menu/item/' . $menu_link->id() . '/edit', $edit, t('Save'));
    $this->assertMenuLink($menu_link->getPluginId(), array(
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'bb',
    ));

    // Saving menu link items ends up on the edit menu page. To check the menu
    // link has the correct language default on edit, go to the menu link edit
    // page first.
    $this->drupalGet('admin/structure/menu/item/' . $menu_link->id() . '/edit');
    // Check that the language selector has the correct default value.
    $this->assertOptionSelected('edit-langcode', 'bb', 'The menu link language was correctly selected.');

    // Edit menu to hide the language select on menu link item add.
     language_save_default_configuration('menu_link_content', 'menu_link_content',  array('langcode' => 'cc', 'language_show' => FALSE));

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
    $menu_name = Unicode::strtolower($this->randomMachineName(16));
    $edit = array(
      'id' => $menu_name,
      'description' => '',
      'label' => $this->randomString(),
      'langcode' => 'en',
    );
    $this->drupalPostForm('admin/structure/menu/add', $edit, t('Save'));

    // Check that the language settings were saved.
    $menu = Menu::load($menu_name);
    $this->assertEqual($menu->language()->getId(), 'en');

    // Remove English language. To do that another language has to be set as
    // default.
    $language = ConfigurableLanguage::load('cs');
    $language->set('default', TRUE);
    $language->save();
    entity_delete_multiple('configurable_language', array('en'));

    // Save the menu again and check if the language is still the same.
    $this->drupalPostForm("admin/structure/menu/manage/$menu_name", array(), t('Save'));
    $menu = Menu::load($menu_name);
    $this->assertEqual($menu->language()->getId(), 'en');
  }

}
