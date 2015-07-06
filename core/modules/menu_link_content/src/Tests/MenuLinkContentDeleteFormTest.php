<?php
/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\MenuLinkContentDeleteFormTest.
 */

namespace Drupal\menu_link_content\Tests;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests the menu link content delete UI.
 *
 * @group Menu
 */
class MenuLinkContentDeleteFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['menu_link_content'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the MenuLinkContentDeleteForm class.
   */
  public function testMenuLinkContentDeleteForm() {
    // Add new menu item.
    $this->drupalPostForm(
      'admin/structure/menu/manage/admin/add',
      [
        'title[0][value]' => t('Front page'),
        'link[0][uri]' => '<front>',
      ],
      t('Save')
    );
    $this->assertText(t('The menu link has been saved.'));

    $menu_link = MenuLinkContent::load(1);
    $this->drupalGet($menu_link->urlInfo('delete-form'));
    $this->assertRaw(t('Are you sure you want to delete the custom menu link %name?', ['%name' => $menu_link->label()]));
    $this->assertLink(t('Cancel'));
    // Make sure cancel link points to link edit
    $this->assertLinkByHref($menu_link->url('edit-form'));

    \Drupal::service('module_installer')->install(['menu_ui']);
    // Make sure cancel URL points to menu_ui route now.
    $this->drupalGet($menu_link->urlInfo('delete-form'));
    $menu = Menu::load($menu_link->getMenuName());
    $this->assertLinkByHref($menu->url('edit-form'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertRaw(t('The menu link %title has been deleted.', ['%title' => $menu_link->label()]));
  }

}
