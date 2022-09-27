<?php

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\menu_ui\Traits\MenuUiTrait;

/**
 * Tests for menu_ui language settings.
 *
 * Create menu and menu links in non-English language, and edit language
 * settings.
 *
 * @group menu_ui
 */
class MenuUiLanguageTest extends BrowserTestBase {

  use MenuUiTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'menu_link_content',
    'menu_ui',
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

    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'administer menu',
    ]));

    // Add some custom languages.
    foreach (['aa', 'bb', 'cc', 'cs'] as $language_code) {
      ConfigurableLanguage::create([
        'id' => $language_code,
        'label' => $this->randomMachineName(),
      ])->save();
    }
  }

  /**
   * Tests menu language settings and the defaults for menu link items.
   */
  public function testMenuLanguage() {
    // Create a test menu to test the various language-related settings.
    // Machine name has to be lowercase.
    $menu_name = mb_strtolower($this->randomMachineName(16));
    $label = $this->randomString();
    $edit = [
      'id' => $menu_name,
      'description' => '',
      'label' => $label,
      'langcode' => 'aa',
    ];
    $this->drupalGet('admin/structure/menu/add');
    $this->submitForm($edit, 'Save');
    ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'menu_link_content')
      ->setDefaultLangcode('bb')
      ->setLanguageAlterable(TRUE)
      ->save();

    // Check menu language.
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode', $edit['langcode'])->isSelected());

    // Test menu link language.
    $link_path = '/';

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = [
      'title[0][value]' => $link_title,
      'link[0][uri]' => $link_path,
    ];
    $this->drupalGet("admin/structure/menu/manage/{$menu_name}/add");
    $this->submitForm($edit, 'Save');
    // Check the link was added with the correct menu link default language.
    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $link_title]);
    $menu_link = reset($menu_links);
    $this->assertMenuLink([
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'bb',
    ], $menu_link->getPluginId());

    // Edit menu link default, changing it to cc.
    ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'menu_link_content')
      ->setDefaultLangcode('cc')
      ->setLanguageAlterable(TRUE)
      ->save();

    // Add a menu link.
    $link_title = $this->randomString();
    $edit = [
      'title[0][value]' => $link_title,
      'link[0][uri]' => $link_path,
    ];
    $this->drupalGet("admin/structure/menu/manage/{$menu_name}/add");
    $this->submitForm($edit, 'Save');
    // Check the link was added with the correct new menu link default language.
    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $link_title]);
    $menu_link = reset($menu_links);
    $this->assertMenuLink([
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'cc',
    ], $menu_link->getPluginId());

    // Now change the language of the new link to 'bb'.
    $edit = [
      'langcode[0][value]' => 'bb',
    ];
    $this->drupalGet('admin/structure/menu/item/' . $menu_link->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $this->assertMenuLink([
      'menu_name' => $menu_name,
      'route_name' => '<front>',
      'langcode' => 'bb',
    ], $menu_link->getPluginId());

    // Saving menu link items ends up on the edit menu page. To check the menu
    // link has the correct language default on edit, go to the menu link edit
    // page first.
    $this->drupalGet('admin/structure/menu/item/' . $menu_link->id() . '/edit');
    // Check that the language selector has the correct default value.
    $this->assertTrue($this->assertSession()->optionExists('edit-langcode-0-value', 'bb')->isSelected());

    // Edit menu to hide the language select on menu link item add.
    ContentLanguageSettings::loadByEntityTypeBundle('menu_link_content', 'menu_link_content')
      ->setDefaultLangcode('cc')
      ->setLanguageAlterable(FALSE)
      ->save();

    // Check that the language selector is not available on menu link add page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $this->assertSession()->fieldNotExists('edit-langcode-0-value');
  }

}
